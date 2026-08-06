<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AudioBook;
use App\Models\AuditLog;
use App\Support\Notify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Audio Book generation (M31):
 *  1. Extract text — pdftotext, with Tesseract OCR (ben+eng) fallback.
 *  2. Resolve language (Bengali script detection when "auto").
 *  3. Narrate in BOTH male and female voices — the external TTS API first
 *     (multipart {file, voice} → audio bytes; male then female, one after
 *     another), espeak-ng as offline fallback — then encode each to MP3.
 * Runs on the queue: the UI responds immediately and is never held up.
 */
class GenerateAudioBook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_CHARS = 120000;

    private const MAX_OCR_PAGES = 40;

    public int $tries = 1;

    public int $timeout = 3600;   // two full narrations of a long document

    public function __construct(public readonly int $audioBookId)
    {
        $this->onConnection('database');
    }

    public function handle(): void
    {
        $book = AudioBook::query()->with('user')->find($this->audioBookId);
        if (! $book) {
            return;
        }

        $disk = Storage::disk('local');

        try {
            // ---- 1. Text ------------------------------------------------
            // Manually edited text is authoritative: never re-extract from
            // the PDF and overwrite the creator's corrections.
            $text = trim((string) $book->text);
            $usedOcr = $book->text_edited ? (bool) $book->used_ocr : false;

            if ($book->source_type === 'pdf' && ! $book->text_edited) {
                $pdfAbs = $disk->path($book->source_path);
                $text = $this->extractPdfText($pdfAbs);

                // OCR the rendered pages when the text layer is missing OR
                // mangled — Bangla PDFs (even Word exports) often store glyphs
                // in visual order with broken ToUnicode maps, producing
                // garbage like "খরগ োশ"; the rendered page is still correct.
                if (mb_strlen($text) < 40 || $this->looksMangledBangla($text)) {
                    $ocr = $this->ocrPdf($pdfAbs);
                    if (mb_strlen($ocr) >= 40) {
                        $text = $ocr;
                        $usedOcr = true;
                    }
                }
            }

            $text = trim(preg_replace("/[ \t]+/u", ' ', (string) $text) ?? '');
            if (mb_strlen($text) < 5) {
                $this->fail_($book, 'No readable text found in the document (even after OCR).');

                return;
            }
            if (mb_strlen($text) > self::MAX_CHARS) {
                $text = mb_substr($text, 0, self::MAX_CHARS);
            }

            // ---- 2. Language --------------------------------------------
            $language = $book->language;
            if ($language === 'auto') {
                $bengali = preg_match_all('/\p{Bengali}/u', $text);
                $language = ($bengali / max(1, mb_strlen(preg_replace('/\s/u', '', $text)))) > 0.15 ? 'bn' : 'en';
            }

            $book->update([
                'language' => $language,
                'characters' => mb_strlen($text),
                'used_ocr' => $usedOcr,
                'text' => $text,
            ]);

            // ---- 3. Narrations -------------------------------------------
            // TEMPORARY: the male/female narrations are commented out — only
            // the Google-backed "Enhanced" narration is generated (and is
            // therefore required). To restore three narrations, put 'male'
            // and 'female' back into the list below.
            $paths = [];
            $durations = [];
            $engine = 'neural';

            foreach ([/* 'male', 'female', */ 'enhanced'] as $voice) {
                $wavRel = "speech/audiobooks/{$book->id}-{$voice}.wav";
                $mp3Rel = "speech/audiobooks/{$book->id}-{$voice}.mp3";
                @mkdir(dirname($disk->path($wavRel)), 0775, true);

                if (! $this->apiSynthesize($book, $text, $voice, $disk->path($wavRel))) {
                    if ($voice === 'enhanced') {
                        // Enhanced is currently the ONLY narration — without it
                        // there is no audio, so the book fails with a clear error.
                        // (When male/female are re-enabled, make this `continue;`
                        // again so enhanced goes back to being optional.)
                        $this->fail_($book, 'The Enhanced narration service is unavailable — please try again later.');

                        return;
                    }
                    $engine = 'espeak';
                    if (! $this->espeakSynthesize($text, $language, $voice, $disk->path($wavRel), $book->id)) {
                        $this->fail_($book, "Narration failed for the {$voice} voice.");

                        return;
                    }
                }

                // Loudness-normalised MP3 (EBU R128 speech target) — consistent,
                // clearer perceived volume across the whole narration.
                exec(sprintf('ffmpeg -y -v error -i %s -af %s -codec:a libmp3lame -q:a 4 %s 2>&1',
                    escapeshellarg($disk->path($wavRel)),
                    escapeshellarg('loudnorm=I=-17:TP=-1.5:LRA=9'),
                    escapeshellarg($disk->path($mp3Rel))), $o, $e);
                if ($e === 0 && $disk->exists($mp3Rel)) {
                    $disk->delete($wavRel);
                    $paths[$voice] = $mp3Rel;
                } else {
                    $paths[$voice] = $wavRel;
                }

                exec(sprintf('ffprobe -v error -show_entries format=duration -of csv=p=0 %s 2>&1',
                    escapeshellarg($disk->path($paths[$voice]))), $d);
                $durations[$voice] = (int) round((float) ($d[0] ?? 0));
                unset($o, $e, $d);
            }

            // Generation complete → READY for the creator to review; submitting
            // for publication is a separate, explicit step.
            $book->update([
                'status' => 'ready',
                'engine' => $engine,
                'audio_male_path' => $paths['male'] ?? null,
                'audio_female_path' => $paths['female'] ?? null,
                'audio_enhanced_path' => $paths['enhanced'] ?? null,
                'duration_male' => $durations['male'] ?? 0,
                'duration_female' => $durations['female'] ?? 0,
                'duration_enhanced' => $durations['enhanced'] ?? 0,
                'error' => null,
            ]);

            // Download protection: package each narration into encrypted HLS
            // right away so the very first play already streams protected.
            foreach ($paths as $voice => $rel) {
                \App\Support\Hls::package($disk->path($rel), 'audiobook', $book->id, $voice);
            }

            AuditLog::record('audiobook_generated', $book, null, [
                'language' => $language, 'engine' => $engine,
                'characters' => $book->characters, 'ocr' => $usedOcr,
                'duration_male' => $durations['male'] ?? 0, 'duration_female' => $durations['female'] ?? 0,
                'duration_enhanced' => $durations['enhanced'] ?? 0,
            ], "Audio book “{$book->title}” narrated (".implode(', ', array_keys($paths)).") ({$language}, {$engine}) — ready for review.");

            $tracks = implode(', ', array_keys($paths));
            Notify::user($book->user, 'speech_ready',
                'Audio book is ready',
                "“{$book->title}” was narrated ({$tracks}) in ".($language === 'bn' ? 'Bangla' : 'English').'. Listen, then press Submit to send it for publication.'.($usedOcr ? ' Text was recovered by OCR — spot-check for errors.' : ''),
                route('admin.audiobooks.show', $book));
        } catch (\Throwable $e) {
            Log::error('[audiobook] failed', ['book' => $book->id, 'error' => $e->getMessage()]);
            $this->fail_($book, $e->getMessage());
        }
    }

    public function failed(\Throwable $e): void
    {
        $book = AudioBook::query()->with('user')->find($this->audioBookId);
        if ($book) {
            $this->fail_($book, $e->getMessage());
        }
    }

    /**
     * External TTS API: POST multipart {file: the original PDF (or the text
     * as a .txt file), voice: male|female} → audio bytes. A short connect
     * timeout makes an unreachable API fail fast (espeak takes over) while
     * the response wait stays generous — narration takes time.
     */
    private function apiSynthesize(AudioBook $book, string $text, string $voice, string $outAbs): bool
    {
        // "enhanced" uses the Google-backed endpoint (file only); male/female
        // use the standard endpoint with the voice form field.
        $enhanced = $voice === 'enhanced';
        $url = trim((string) config($enhanced ? 'services.tts.enhanced_url' : 'services.tts.api_url'));
        if ($url === '') {
            return false;
        }

        try {
            $disk = Storage::disk('local');

            // Edited text is authoritative — once the creator has corrected
            // the text, narrate THAT, never the original PDF.
            if ($book->source_type === 'pdf' && ! $book->text_edited && $book->source_path && $disk->exists($book->source_path)) {
                $contents = $disk->get($book->source_path);
                $filename = 'book.pdf';
            } else {
                $contents = $text;
                $filename = 'book.txt';
            }

            $response = Http::connectTimeout((int) config('services.tts.connect_timeout', 5))
                ->timeout((int) config('services.tts.timeout', 1800))
                ->attach('file', $contents, $filename)
                ->post($url, $enhanced ? [] : ['voice' => $voice]);

            if (! $response->ok() || strlen($response->body()) < 1000) {
                Log::info('[audiobook] TTS API returned no audio, falling back to espeak', [
                    'status' => $response->status(), 'bytes' => strlen($response->body()),
                ]);

                return false;
            }

            file_put_contents($outAbs, $response->body());

            // The response must actually BE audio — probe it before accepting.
            exec(sprintf('ffprobe -v error -show_entries format=duration -of csv=p=0 %s 2>&1',
                escapeshellarg($outAbs)), $probe, $probeExit);
            if ($probeExit !== 0 || (float) ($probe[0] ?? 0) < 0.5) {
                Log::warning('[audiobook] TTS API response is not playable audio, falling back', [
                    'probe_exit' => $probeExit,
                ]);
                @unlink($outAbs);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::info('[audiobook] TTS API unavailable, falling back to espeak', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function espeakSynthesize(string $text, string $language, string $voice, string $outAbs, int $bookId): bool
    {
        $voiceId = ($language === 'bn' ? 'bn' : 'en-us').($voice === 'male' ? '+m3' : '+f2');
        $textFile = Storage::disk('local')->path("speech/tmp-book-{$bookId}-{$voice}.txt");
        file_put_contents($textFile, $text);

        exec(sprintf('espeak-ng -v %s -s 155 -f %s -w %s 2>&1',
            escapeshellarg($voiceId), escapeshellarg($textFile), escapeshellarg($outAbs)), $out, $exit);
        @unlink($textFile);

        return $exit === 0 && is_file($outAbs);
    }

    /**
     * Fingerprint of a broken Bangla text layer: dependent vowel signs (কার)
     * can never begin a word in valid Bangla, but glyph-order extraction
     * produces them constantly (split vowels detached from their consonant).
     */
    private function looksMangledBangla(string $text): bool
    {
        $bengali = preg_match_all('/\p{Bengali}/u', $text);
        if ($bengali < 40) {
            return false;   // not Bangla-dominant — nothing to judge
        }

        $brokenStarts = preg_match_all('/(?:^|\s)[\x{09BE}-\x{09CC}\x{09D7}\x{0981}-\x{0983}\x{09CD}]/u', $text);
        $words = max(1, preg_match_all('/\S+/u', $text));

        return ($brokenStarts / $words) > 0.02;
    }

    private function extractPdfText(string $pdfAbs): string
    {
        exec(sprintf('pdftotext -enc UTF-8 -nopgbrk %s - 2>/dev/null', escapeshellarg($pdfAbs)), $lines, $exit);

        return $exit === 0 ? trim(implode("\n", $lines)) : '';
    }

    private function ocrPdf(string $pdfAbs): string
    {
        $tmp = rtrim(sys_get_temp_dir(), '/')."/ocr-book-{$this->audioBookId}";
        @mkdir($tmp, 0775, true);

        exec(sprintf('pdftoppm -r 200 -png -l %d %s %s 2>/dev/null',
            self::MAX_OCR_PAGES, escapeshellarg($pdfAbs), escapeshellarg("{$tmp}/page")));

        $text = '';
        foreach (glob("{$tmp}/page*.png") ?: [] as $page) {
            exec(sprintf('tesseract %s stdout -l ben+eng 2>/dev/null', escapeshellarg($page)), $lines);
            $text .= implode("\n", $lines)."\n";
            $lines = [];
            @unlink($page);
        }
        @rmdir($tmp);

        return trim($text);
    }

    private function fail_(AudioBook $book, string $reason): void
    {
        $book->update(['status' => 'failed', 'error' => mb_substr($reason, 0, 1000)]);

        Notify::user($book->user, 'rights_status',
            'Audio book generation failed',
            "“{$book->title}”: {$reason}",
            route('admin.audiobooks.index'));
    }
}
