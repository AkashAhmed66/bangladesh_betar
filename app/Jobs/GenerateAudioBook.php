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
 *  3. Narrate in BOTH male and female voices — neural sidecar first
 *     (Piper English / MMS Bangla), espeak-ng as offline fallback — then
 *     encode each to MP3.
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
            $text = trim((string) $book->text);
            $usedOcr = false;

            if ($book->source_type === 'pdf') {
                $pdfAbs = $disk->path($book->source_path);
                $text = $this->extractPdfText($pdfAbs);

                if (mb_strlen($text) < 40) {
                    $text = $this->ocrPdf($pdfAbs);
                    $usedOcr = true;
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

            // ---- 3. Both voices -----------------------------------------
            $paths = [];
            $durations = [];
            $engine = 'neural';

            foreach (['male', 'female'] as $voice) {
                $wavRel = "speech/audiobooks/{$book->id}-{$voice}.wav";
                $mp3Rel = "speech/audiobooks/{$book->id}-{$voice}.mp3";
                @mkdir(dirname($disk->path($wavRel)), 0775, true);

                if (! $this->neuralSynthesize($text, $language, $voice, $disk->path($wavRel))) {
                    $engine = 'espeak';
                    if (! $this->espeakSynthesize($text, $language, $voice, $disk->path($wavRel), $book->id)) {
                        $this->fail_($book, "Narration failed for the {$voice} voice.");

                        return;
                    }
                }

                exec(sprintf('ffmpeg -y -v error -i %s -codec:a libmp3lame -q:a 4 %s 2>&1',
                    escapeshellarg($disk->path($wavRel)), escapeshellarg($disk->path($mp3Rel))), $o, $e);
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

            // Generation complete → straight into the approval queue.
            $book->update([
                'status' => 'pending_approval',
                'submitted_at' => now(),
                'engine' => $engine,
                'audio_male_path' => $paths['male'],
                'audio_female_path' => $paths['female'],
                'duration_male' => $durations['male'],
                'duration_female' => $durations['female'],
                'error' => null,
            ]);

            AuditLog::record('audiobook_generated', $book, null, [
                'language' => $language, 'engine' => $engine,
                'characters' => $book->characters, 'ocr' => $usedOcr,
                'duration_male' => $durations['male'], 'duration_female' => $durations['female'],
            ], "Audio book “{$book->title}” narrated in both voices ({$language}, {$engine}) and submitted for approval.");

            Notify::user($book->user, 'speech_ready',
                'Audio book generated & sent for approval',
                "“{$book->title}” was narrated in both male and female ".($language === 'bn' ? 'Bangla' : 'English').' voices and submitted for approval automatically.'.($usedOcr ? ' Text was recovered by OCR — spot-check for errors.' : ''),
                route('admin.audiobooks.show', $book));

            Notify::permission('audiobooks.approve', 'needs_approval',
                'Audio book needs your approval',
                ($book->user?->name ?? 'A creator')." submitted the audio book “{$book->title}” (".($language === 'bn' ? 'Bangla' : 'English').', male + female narrations).',
                route('admin.audiobooks.show', $book),
                except: $book->user_id);
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

    private function neuralSynthesize(string $text, string $language, string $voice, string $outAbs): bool
    {
        $base = rtrim((string) config('services.tts.base_url'), '/');
        if ($base === '') {
            return false;
        }

        try {
            if (! Http::timeout(3)->get("{$base}/health")->ok()) {
                return false;
            }

            $response = Http::timeout((int) config('services.tts.timeout', 1800))
                ->post("{$base}/synthesize", ['text' => $text, 'language' => $language, 'voice' => $voice]);

            if (! $response->ok() || strlen($response->body()) < 1000) {
                return false;
            }

            file_put_contents($outAbs, $response->body());

            // MMS ships one Bangla voice — derive the male variant by pitch
            // shifting down three semitones (duration preserved).
            if ($language === 'bn' && $voice === 'male') {
                $shifted = "{$outAbs}.shift.wav";
                exec(sprintf('ffmpeg -y -v error -i %s -af %s %s 2>&1',
                    escapeshellarg($outAbs),
                    escapeshellarg('asetrate=16000*0.8409,aresample=16000,atempo=1.1892'),
                    escapeshellarg($shifted)), $o, $e);
                if ($e === 0 && is_file($shifted)) {
                    rename($shifted, $outAbs);
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::info('[audiobook] neural TTS unavailable, falling back to espeak', ['error' => $e->getMessage()]);

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
