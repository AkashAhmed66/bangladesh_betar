<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\SpeechConversion;
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
 * Offline PDF/text → speech (M31):
 *  1. Extract text — pdftotext for digital PDFs; Tesseract OCR (ben+eng)
 *     fallback for scanned ones. Pasted text skips extraction.
 *  2. Resolve the language — Bangla is detected from the Bengali script range.
 *  3. Synthesize with espeak-ng (fully offline; bn + en, male/female
 *     variants), then encode to MP3 with ffmpeg.
 */
class SynthesizeSpeech implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_CHARS = 120000;   // ~2h of speech — runtime sanity cap

    private const MAX_OCR_PAGES = 40;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public readonly int $conversionId)
    {
        $this->onConnection('database');
    }

    public function handle(): void
    {
        $conversion = SpeechConversion::query()->with('user')->find($this->conversionId);
        if (! $conversion) {
            return;
        }

        $disk = Storage::disk('local');

        try {
            // ---- 1. Text ------------------------------------------------
            $conversion->update(['status' => 'extracting']);

            $text = trim((string) $conversion->source_text);
            $usedOcr = false;

            if ($conversion->source_type === 'pdf') {
                $pdfAbs = $disk->path($conversion->source_path);
                $text = $this->extractPdfText($pdfAbs);

                // A scanned PDF yields (almost) nothing — fall back to OCR.
                if (mb_strlen($text) < 40) {
                    $text = $this->ocrPdf($pdfAbs);
                    $usedOcr = true;
                }
            }

            $text = trim(preg_replace("/[ \t]+/u", ' ', (string) $text) ?? '');
            if (mb_strlen($text) < 5) {
                $this->fail_($conversion, 'No readable text found in the document (even after OCR).');

                return;
            }
            if (mb_strlen($text) > self::MAX_CHARS) {
                $text = mb_substr($text, 0, self::MAX_CHARS);
            }

            // ---- 2. Language --------------------------------------------
            $language = $conversion->language;
            if ($language === 'auto') {
                $bengali = preg_match_all('/\p{Bengali}/u', $text);
                $language = ($bengali / max(1, mb_strlen(preg_replace('/\s/u', '', $text)))) > 0.15 ? 'bn' : 'en';
            }

            // ---- 3. Speech ----------------------------------------------
            $conversion->update([
                'status' => 'synthesizing',
                'language' => $language,
                'characters' => mb_strlen($text),
                'used_ocr' => $usedOcr,
                'source_text' => mb_substr($text, 0, 60000),
            ]);

            $wavRel = "speech/audio/{$conversion->id}.wav";
            $mp3Rel = "speech/audio/{$conversion->id}.mp3";
            @mkdir(dirname($disk->path($wavRel)), 0775, true);

            // Neural sidecar first (Piper/MMS — natural voices); espeak-ng is
            // the always-available offline fallback.
            $engine = $this->neuralSynthesize($text, $language, $conversion->voice, $disk->path($wavRel))
                ? 'neural'
                : 'espeak';

            if ($engine === 'espeak') {
                $voice = ($language === 'bn' ? 'bn' : 'en-us').($conversion->voice === 'male' ? '+m3' : '+f2');

                $textFile = $disk->path("speech/tmp-{$conversion->id}.txt");
                file_put_contents($textFile, $text);

                $cmd = sprintf('espeak-ng -v %s -s 155 -f %s -w %s 2>&1',
                    escapeshellarg($voice), escapeshellarg($textFile), escapeshellarg($disk->path($wavRel)));
                exec($cmd, $out, $exit);
                @unlink($textFile);

                if ($exit !== 0 || ! $disk->exists($wavRel)) {
                    Log::warning('[speech] espeak failed', ['conversion' => $conversion->id, 'exit' => $exit, 'out' => array_slice($out, -3)]);
                    $this->fail_($conversion, 'Speech synthesis failed.');

                    return;
                }
            }

            exec(sprintf('ffmpeg -y -v error -i %s -codec:a libmp3lame -q:a 4 %s 2>&1',
                escapeshellarg($disk->path($wavRel)), escapeshellarg($disk->path($mp3Rel))), $o2, $e2);
            if ($e2 === 0 && $disk->exists($mp3Rel)) {
                $disk->delete($wavRel);
                $outputRel = $mp3Rel;
            } else {
                $outputRel = $wavRel;    // mp3 encode failed — ship the wav
            }

            $duration = 0;
            exec(sprintf('ffprobe -v error -show_entries format=duration -of csv=p=0 %s 2>&1',
                escapeshellarg($disk->path($outputRel))), $d);
            $duration = (int) round((float) ($d[0] ?? 0));

            $conversion->update([
                'status' => 'done',
                'output_path' => $outputRel,
                'duration_seconds' => $duration,
                'engine' => $engine,
                'error' => null,
            ]);

            AuditLog::record('speech_synthesized', $conversion, null, [
                'language' => $language, 'voice' => $conversion->voice,
                'characters' => $conversion->characters, 'duration' => $duration, 'ocr' => $usedOcr,
            ], "PDF-to-Speech #{$conversion->id} “{$conversion->title}” synthesized ({$language}, {$conversion->voice}, ".gmdate('i:s', $duration).').');

            Notify::user($conversion->user, 'speech_ready',
                'Speech is ready',
                "“{$conversion->title}” was converted to ".($language === 'bn' ? 'Bangla' : 'English')." {$conversion->voice} speech (".gmdate('i:s', $duration).').'.($usedOcr ? ' Text was recovered by OCR — spot-check for errors.' : ''),
                route('admin.speech.index'));
        } catch (\Throwable $e) {
            Log::error('[speech] failed', ['conversion' => $conversion->id, 'error' => $e->getMessage()]);
            $this->fail_($conversion, $e->getMessage());
        }
    }

    public function failed(\Throwable $e): void
    {
        $conversion = SpeechConversion::query()->with('user')->find($this->conversionId);
        if ($conversion) {
            $this->fail_($conversion, $e->getMessage());
        }
    }

    /**
     * Try the neural sidecar (Piper for English, MMS for Bangla). Returns
     * false on any problem so the caller falls back to espeak-ng. For Bangla
     * "male", the single MMS voice is pitch-shifted down three semitones
     * (duration preserved) to derive the second gender.
     */
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
                Log::info('[speech] neural TTS returned no audio, falling back', ['status' => $response->status()]);

                return false;
            }

            file_put_contents($outAbs, $response->body());

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
            Log::info('[speech] neural TTS unavailable, falling back to espeak', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /** pdftotext — extracts the Unicode text layer of a digital PDF. */
    private function extractPdfText(string $pdfAbs): string
    {
        exec(sprintf('pdftotext -enc UTF-8 -nopgbrk %s - 2>/dev/null', escapeshellarg($pdfAbs)), $lines, $exit);

        return $exit === 0 ? trim(implode("\n", $lines)) : '';
    }

    /** Tesseract OCR (Bengali + English) for scanned PDFs. */
    private function ocrPdf(string $pdfAbs): string
    {
        $tmp = rtrim(sys_get_temp_dir(), '/')."/ocr-{$this->conversionId}";
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

    private function fail_(SpeechConversion $conversion, string $reason): void
    {
        $conversion->update(['status' => 'failed', 'error' => mb_substr($reason, 0, 1000)]);

        Notify::user($conversion->user, 'rights_status',
            'Speech conversion failed',
            "“{$conversion->title}”: {$reason}",
            route('admin.speech.index'));
    }
}
