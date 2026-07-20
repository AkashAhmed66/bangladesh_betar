<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Generates royalty-free demo audio (synthesized melodies) so the public
 * portal can actually stream something in local/demo environments where
 * the real archive media files are not present.
 *
 * Files land on the "local" disk under demo-audio/:
 *   - track-01.wav .. track-12.wav  (~72s musical beds, one is picked
 *     deterministically per asset by PlaybackController)
 *   - ad-01.wav .. ad-03.wav        (~9s jingles used as pre-roll ads)
 */
class GenerateDemoAudio extends Command
{
    protected $signature = 'demo:audio {--force : Regenerate even if files exist}';

    protected $description = 'Synthesize demo audio files used as streaming fallbacks for the public portal';

    private const SAMPLE_RATE = 22050;

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('demo-audio');

        // Pentatonic-flavoured scales (semitone offsets from the root) keep
        // every random walk consonant.
        $scales = [
            [0, 2, 4, 7, 9],        // major pentatonic
            [0, 3, 5, 7, 10],       // minor pentatonic
            [0, 2, 5, 7, 9],        // suspended
            [0, 2, 4, 7, 11],       // maj7 colour
        ];

        for ($i = 1; $i <= 12; $i++) {
            $path = sprintf('demo-audio/track-%02d.wav', $i);
            if ($disk->exists($path) && ! $this->option('force')) {
                $this->line("skip  {$path}");

                continue;
            }

            mt_srand($i * 7919);
            $root = 110.0 * pow(2, (($i * 5) % 12) / 12);   // rotate root note per track
            $scale = $scales[$i % count($scales)];
            $bpm = 68 + (($i * 13) % 36);                    // 68..104 bpm

            $pcm = $this->renderTrack(72.0, $root, $scale, $bpm);
            $disk->put($path, $this->wav($pcm));
            $this->info("wrote {$path} (".round(strlen($pcm) / 1048576, 1)."MB)");
        }

        for ($i = 1; $i <= 3; $i++) {
            $path = sprintf('demo-audio/ad-%02d.wav', $i);
            if ($disk->exists($path) && ! $this->option('force')) {
                $this->line("skip  {$path}");

                continue;
            }

            mt_srand($i * 1231);
            $pcm = $this->renderJingle(9.0, 220.0 * pow(2, ($i * 4 % 12) / 12));
            $disk->put($path, $this->wav($pcm));
            $this->info("wrote {$path}");
        }

        $this->info('Demo audio ready.');

        return self::SUCCESS;
    }

    /**
     * Render a gentle generative piece: a slow bass drone, an arpeggio that
     * walks the scale, and an airy echo of the melody one octave up.
     */
    private function renderTrack(float $seconds, float $root, array $scale, int $bpm): string
    {
        $n = (int) ($seconds * self::SAMPLE_RATE);
        $buf = array_fill(0, $n, 0.0);

        $beat = 60.0 / $bpm;
        $step = $beat / 2;                       // eighth notes
        $degree = 0;
        $octave = 1;

        // Chord roots move every 4 beats through a I-vi-IV-V style loop.
        $progression = [0, 4, 3, 1, 0, 3, 4, 1];

        for ($t = 0.0, $stepIdx = 0; $t < $seconds - $step; $t += $step, $stepIdx++) {
            $bar = (int) floor($t / ($beat * 4));
            $chordDegree = $progression[$bar % count($progression)];

            // Bass: whole-bar root, mellow triangle-ish tone.
            if ($stepIdx % 8 === 0) {
                $f = $root * pow(2, $scale[$chordDegree % count($scale)] / 12) / 2;
                $this->addNote($buf, $t, $beat * 4, $f, 0.22, 0.6, 0.9);
            }

            // Melody: random walk on the scale, skips some steps for breathing room.
            if (mt_rand(0, 99) < 72) {
                $degree += [-2, -1, -1, 0, 1, 1, 2][mt_rand(0, 6)];
                $degree = max(-3, min(9, $degree));
                $idx = (($degree % count($scale)) + count($scale)) % count($scale);
                $oct = 1 + intdiv($degree + 3, count($scale));
                $f = $root * pow(2, $scale[$idx] / 12) * pow(2, $oct);
                $len = $step * (mt_rand(0, 9) < 3 ? 2 : 1);
                $this->addNote($buf, $t, $len, $f, 0.16, 0.01, 0.35);
                // Airy octave echo.
                $this->addNote($buf, $t + $step / 2, $len, $f * 2, 0.05, 0.02, 0.5);
            }
        }

        return $this->toPcm($buf, $seconds);
    }

    private function renderJingle(float $seconds, float $root): string
    {
        $n = (int) ($seconds * self::SAMPLE_RATE);
        $buf = array_fill(0, $n, 0.0);

        // Bright ascending motif, repeated with a closing chord.
        $motif = [0, 4, 7, 12, 7, 4, 0, 12];
        $t = 0.25;
        foreach ($motif as $semi) {
            $this->addNote($buf, $t, 0.5, $root * pow(2, $semi / 12) * 2, 0.2, 0.01, 0.3);
            $t += 0.45;
        }
        foreach ([0, 4, 7] as $semi) {
            $this->addNote($buf, $t + 0.3, $seconds - $t - 0.6, $root * pow(2, $semi / 12) * 2, 0.14, 0.05, 0.8);
        }

        return $this->toPcm($buf, $seconds);
    }

    /**
     * Additively render one note (sine + one soft harmonic) with an
     * attack/decay envelope into the float buffer.
     */
    private function addNote(array &$buf, float $start, float $dur, float $freq, float $amp, float $attack, float $release): void
    {
        $sr = self::SAMPLE_RATE;
        $s0 = (int) ($start * $sr);
        $count = (int) ($dur * $sr);
        $n = count($buf);
        $attackSamples = max(1, (int) ($attack * $sr));
        $releaseSamples = max(1, (int) ($release * $sr * $dur));
        $w = 2 * M_PI * $freq / $sr;

        for ($i = 0; $i < $count; $i++) {
            $idx = $s0 + $i;
            if ($idx < 0 || $idx >= $n) {
                continue;
            }
            $env = min(1.0, $i / $attackSamples) * min(1.0, ($count - $i) / $releaseSamples);
            $buf[$idx] += $amp * $env * (sin($w * $i) + 0.35 * sin(2 * $w * $i) + 0.12 * sin(3 * $w * $i));
        }
    }

    /** Normalize, fade edges and pack the float buffer into 16-bit PCM. */
    private function toPcm(array $buf, float $seconds): string
    {
        $n = count($buf);
        $peak = 0.0001;
        foreach ($buf as $v) {
            $a = abs($v);
            if ($a > $peak) {
                $peak = $a;
            }
        }
        $gain = 0.85 / $peak;

        $fade = (int) (0.8 * self::SAMPLE_RATE);
        $out = '';
        $chunk = [];
        for ($i = 0; $i < $n; $i++) {
            $v = $buf[$i] * $gain;
            if ($i < $fade) {
                $v *= $i / $fade;
            }
            if ($i > $n - $fade) {
                $v *= ($n - $i) / $fade;
            }
            $chunk[] = (int) round(max(-1.0, min(1.0, $v)) * 32767);
            if (count($chunk) === 8192) {
                $out .= pack('s*', ...$chunk);
                $chunk = [];
            }
        }
        if ($chunk !== []) {
            $out .= pack('s*', ...$chunk);
        }

        return $out;
    }

    /** Wrap raw 16-bit mono PCM in a RIFF/WAVE header. */
    private function wav(string $pcm): string
    {
        $sr = self::SAMPLE_RATE;
        $dataLen = strlen($pcm);

        return 'RIFF'.pack('V', 36 + $dataLen).'WAVE'
            .'fmt '.pack('V', 16).pack('v', 1).pack('v', 1)     // PCM, mono
            .pack('V', $sr).pack('V', $sr * 2)                  // sample rate, byte rate
            .pack('v', 2).pack('v', 16)                         // block align, bits
            .'data'.pack('V', $dataLen).$pcm;
    }
}
