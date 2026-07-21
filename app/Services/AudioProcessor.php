<?php

declare(strict_types=1);

namespace App\Services;

/**
 * M02 — technical analysis of an ingested audio file (FR-ING-09/10).
 *
 * Uses ffprobe/ffmpeg when available (all formats, EBU R128 loudness, real
 * proxy transcoding). Degrades gracefully to a native PHP WAV parser so the
 * platform still ingests WAV/BWF files on hosts without ffmpeg (e.g. Windows
 * dev machines); for other formats without ffmpeg the browser computes the
 * waveform on first view and posts it back.
 */
class AudioProcessor
{
    /** @return array{
     *   duration_seconds:int, sample_rate:?int, bit_depth:?int, channels:?int,
     *   bitrate_kbps:?int, loudness_lufs:?float, peak_db:?float,
     *   silence_percent:?float, waveform_peaks:?array, analyzer:string
     * } */
    public function analyze(string $absolutePath): array
    {
        if ($this->hasFfmpeg()) {
            $data = $this->analyzeWithFfmpeg($absolutePath);
            if ($data !== null) {
                return $data + ['analyzer' => 'ffmpeg'];
            }
        }

        if ($this->isWav($absolutePath)) {
            return $this->analyzeWav($absolutePath) + ['analyzer' => 'php-wav'];
        }

        // Unknown format, no ffmpeg — store the file; the client fills peaks in.
        return [
            'duration_seconds' => 0, 'sample_rate' => null, 'bit_depth' => null,
            'channels' => null, 'bitrate_kbps' => null, 'loudness_lufs' => null,
            'peak_db' => null, 'silence_percent' => null, 'waveform_peaks' => null,
            'analyzer' => 'none',
        ];
    }

    public function hasFfmpeg(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        if (! function_exists('exec')) {
            return $has = false;
        }
        @exec($this->bin('ffprobe').' -version 2>&1', $out, $code);

        return $has = ($code === 0);
    }

    /* ----------------------------- ffmpeg ----------------------------- */

    private function analyzeWithFfmpeg(string $path): ?array
    {
        $escaped = escapeshellarg($path);

        @exec($this->bin('ffprobe').' -v quiet -print_format json -show_format -show_streams '.$escaped.' 2>&1', $out, $code);
        if ($code !== 0) {
            return null;
        }
        $probe = json_decode(implode('', $out), true);
        $audio = collect($probe['streams'] ?? [])->firstWhere('codec_type', 'audio') ?? [];

        $duration = (float) ($probe['format']['duration'] ?? $audio['duration'] ?? 0);
        $loudness = $this->loudnessWithFfmpeg($path);

        return [
            'duration_seconds' => (int) round($duration),
            'sample_rate' => isset($audio['sample_rate']) ? (int) $audio['sample_rate'] : null,
            'bit_depth' => $this->bitDepth($audio),
            'channels' => isset($audio['channels']) ? (int) $audio['channels'] : null,
            'bitrate_kbps' => isset($probe['format']['bit_rate']) ? (int) round($probe['format']['bit_rate'] / 1000) : null,
            'loudness_lufs' => $loudness['lufs'],
            'peak_db' => $loudness['peak'],
            'silence_percent' => $this->silenceWithFfmpeg($path, $duration),
            'waveform_peaks' => $this->peaksWithFfmpeg($path, 200),
        ];
    }

    /** EBU R128 integrated loudness + true peak. */
    private function loudnessWithFfmpeg(string $path): array
    {
        @exec($this->bin('ffmpeg').' -i '.escapeshellarg($path).' -af ebur128=peak=true -f null - 2>&1', $out);
        $text = implode("\n", $out);
        $lufs = preg_match('/I:\s*(-?\d+\.?\d*)\s*LUFS/', $text, $m) ? (float) $m[1] : null;
        $peak = preg_match('/Peak:\s*(-?\d+\.?\d*)\s*dBFS/', $text, $p) ? (float) $p[1] : null;

        return ['lufs' => $lufs, 'peak' => $peak];
    }

    private function silenceWithFfmpeg(string $path, float $duration): ?float
    {
        if ($duration <= 0) {
            return null;
        }
        @exec($this->bin('ffmpeg').' -i '.escapeshellarg($path).' -af silencedetect=noise=-40dB:d=0.5 -f null - 2>&1', $out);
        $silence = 0.0;
        foreach ($out as $line) {
            if (preg_match('/silence_duration:\s*(\d+\.?\d*)/', $line, $m)) {
                $silence += (float) $m[1];
            }
        }

        return round(min(100, $silence / $duration * 100), 2);
    }

    /** Decode to raw mono 8-bit and downsample to N amplitude buckets. */
    private function peaksWithFfmpeg(string $path, int $buckets): ?array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'wf').'.raw';
        @exec($this->bin('ffmpeg').' -i '.escapeshellarg($path).' -ac 1 -ar 8000 -f u8 '.escapeshellarg($tmp).' -y 2>&1', $o, $code);
        if ($code !== 0 || ! is_file($tmp)) {
            return null;
        }
        $raw = file_get_contents($tmp);
        @unlink($tmp);
        if ($raw === false || strlen($raw) === 0) {
            return null;
        }

        return $this->downsampleUnsigned8($raw, $buckets);
    }

    private function bitDepth(array $audio): ?int
    {
        $fmt = (string) ($audio['sample_fmt'] ?? '');

        return match (true) {
            str_contains($fmt, 's16') => 16,
            str_contains($fmt, 's32'), str_contains($fmt, 'flt') => 24,
            str_contains($fmt, 'u8') => 8,
            default => null,
        };
    }

    /* --------------------------- native WAV --------------------------- */

    private function isWav(string $path): bool
    {
        $fh = @fopen($path, 'rb');
        if (! $fh) {
            return false;
        }
        $header = fread($fh, 12);
        fclose($fh);

        return strlen($header) >= 12 && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WAVE';
    }

    /** Parse a PCM WAV header + sample the data chunk for peaks/RMS/peak-dB. */
    private function analyzeWav(string $path): array
    {
        $fh = fopen($path, 'rb');
        fread($fh, 12); // RIFF....WAVE

        $channels = 2;
        $sampleRate = 44100;
        $bits = 16;
        $dataOffset = null;
        $dataSize = 0;

        while (! feof($fh)) {
            $chunkHeader = fread($fh, 8);
            if (strlen($chunkHeader) < 8) {
                break;
            }
            $id = substr($chunkHeader, 0, 4);
            $size = unpack('V', substr($chunkHeader, 4, 4))[1];

            if ($id === 'fmt ') {
                $fmt = fread($fh, $size);
                $channels = unpack('v', substr($fmt, 2, 2))[1] ?: 2;
                $sampleRate = unpack('V', substr($fmt, 4, 4))[1] ?: 44100;
                $bits = unpack('v', substr($fmt, 14, 2))[1] ?: 16;
                if ($size % 2 === 1) {
                    fread($fh, 1);
                }
            } elseif ($id === 'data') {
                $dataOffset = ftell($fh);
                $dataSize = $size;
                break;
            } else {
                fseek($fh, $size + ($size % 2), SEEK_CUR);
            }
        }

        $byteRate = $sampleRate * $channels * ($bits / 8);
        $duration = $byteRate > 0 ? $dataSize / $byteRate : 0;

        $peaks = null;
        $peakDb = null;
        $rmsDb = null;
        $silencePercent = null;

        if ($dataOffset !== null && $dataSize > 0 && $bits === 16) {
            [$peaks, $peakDb, $rmsDb, $silencePercent] = $this->sampleWav16($fh, $dataOffset, $dataSize, $channels, 200);
        }
        fclose($fh);

        return [
            'duration_seconds' => (int) round($duration),
            'sample_rate' => $sampleRate,
            'bit_depth' => $bits,
            'channels' => $channels,
            'bitrate_kbps' => (int) round($byteRate * 8 / 1000),
            'loudness_lufs' => $rmsDb !== null ? round($rmsDb - 3, 2) : null, // rough LUFS proxy
            'peak_db' => $peakDb,
            'silence_percent' => $silencePercent,
            'waveform_peaks' => $peaks,
        ];
    }

    /** @return array{0:array<float>,1:?float,2:?float,3:?float} */
    private function sampleWav16($fh, int $offset, int $dataSize, int $channels, int $buckets): array
    {
        $totalFrames = (int) ($dataSize / (2 * $channels));
        if ($totalFrames <= 0) {
            return [array_fill(0, $buckets, 0.05), null, null, null];
        }
        $framesPerBucket = max(1, (int) ceil($totalFrames / $buckets));
        // Cap reads for very large files: step through frames.
        $step = max(1, (int) floor($framesPerBucket / 400)); // sample ~400 frames/bucket max

        fseek($fh, $offset);
        $peaks = [];
        $globalPeak = 0;
        $sumSquares = 0.0;
        $sampleCount = 0;
        $silentFrames = 0;
        $frameBytes = 2 * $channels;

        for ($b = 0; $b < $buckets; $b++) {
            $bucketPeak = 0;
            $start = $b * $framesPerBucket;
            for ($i = 0; $i < $framesPerBucket; $i += $step) {
                fseek($fh, $offset + ($start + $i) * $frameBytes);
                $chunk = fread($fh, 2);
                if (strlen($chunk) < 2) {
                    break;
                }
                $sample = unpack('s', $chunk)[1];
                $abs = abs($sample);
                $bucketPeak = max($bucketPeak, $abs);
                $globalPeak = max($globalPeak, $abs);
                $sumSquares += $sample * $sample;
                $sampleCount++;
                if ($abs < 328) { // ~ -40 dBFS
                    $silentFrames++;
                }
            }
            $peaks[] = round($bucketPeak / 32768, 3);
        }

        $peakDb = $globalPeak > 0 ? round(20 * log10($globalPeak / 32768), 2) : null;
        $rms = $sampleCount > 0 ? sqrt($sumSquares / $sampleCount) : 0;
        $rmsDb = $rms > 0 ? round(20 * log10($rms / 32768), 2) : null;
        $silencePercent = $sampleCount > 0 ? round($silentFrames / $sampleCount * 100, 2) : null;

        return [$peaks, $peakDb, $rmsDb, $silencePercent];
    }

    /** @return array<float> */
    private function downsampleUnsigned8(string $raw, int $buckets): array
    {
        $len = strlen($raw);
        $per = max(1, (int) floor($len / $buckets));
        $peaks = [];
        for ($b = 0; $b < $buckets; $b++) {
            $peak = 0;
            $start = $b * $per;
            $end = min($len, $start + $per);
            $stepp = max(1, (int) floor($per / 400));
            for ($i = $start; $i < $end; $i += $stepp) {
                $peak = max($peak, abs(ord($raw[$i]) - 128));
            }
            $peaks[] = round($peak / 128, 3);
        }

        return $peaks;
    }

    /** Resolve the ffmpeg/ffprobe binary path (respects config('audio.ffmpeg_path')). */
    public function binary(string $name): string
    {
        return $this->bin($name);
    }

    private function bin(string $name): string
    {
        $configured = config('audio.ffmpeg_path');

        return $configured ? rtrim($configured, '/\\').DIRECTORY_SEPARATOR.$name : $name;
    }
}
