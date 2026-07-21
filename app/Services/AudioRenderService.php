<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Compiles a non-destructive edit/enhancement operation chain into a single
 * deterministic ffmpeg command and runs it (M12 — audio editing & restoration).
 *
 * The operation list (EDL) comes from the Audio Studio; the render always
 * writes a NEW file, never touching the source (FR-EDT-01). Every operation
 * maps to a well-formed ffmpeg audio filter or output flag — see the catalogue
 * in docs/AUDIO_EDITING_ROADMAP.md.
 */
class AudioRenderService
{
    /** Canonical processing order — ops are applied in this sequence regardless
     *  of the order the UI sends them, so the chain is always sensible. */
    private const ORDER = [
        'trim', 'cut', 'reverse',
        'declip', 'declick', 'denoise', 'dehum',
        'eq', 'compress', 'limit', 'exciter',
        'gain', 'normalize',
        'pitch', 'tempo',
        'silence_remove', 'fade',
        'channels', 'resample', 'export',
    ];

    public function __construct(private readonly AudioProcessor $processor) {}

    public function available(): bool
    {
        return $this->processor->hasFfmpeg();
    }

    /**
     * Build the full ffmpeg argument list.
     *
     * @param  array  $ops  operation chain
     * @param  array  $ctx  ['sample_rate' => int, 'duration' => float]
     * @return array{args: array<string>, filters: string, output: array<string>}
     */
    public function buildArgs(string $input, string $output, array $ops, array $ctx = []): array
    {
        $sr = (int) ($ctx['sample_rate'] ?? 44100) ?: 44100;
        $filters = [];
        $outFlags = [];
        $metadata = [];

        $byOp = [];
        foreach ($ops as $op) {
            $byOp[$op['op'] ?? ''][] = $op;
        }

        $finalDuration = $this->estimateDuration($ops, (float) ($ctx['duration'] ?? 0));

        foreach (self::ORDER as $type) {
            foreach ($byOp[$type] ?? [] as $op) {
                match ($type) {
                    'trim', 'cut' => null, // handled together below
                    'reverse' => $filters[] = 'areverse',
                    'declip' => $filters[] = 'adeclip',
                    'declick' => $filters[] = 'adeclick',
                    'denoise' => $filters[] = 'afftdn=nr='.$this->clamp((float) ($op['strength'] ?? 0.5) * 30 + 6, 3, 40),
                    'dehum' => $filters[] = $this->dehum((int) ($op['freq'] ?? 50)),
                    'eq' => $filters = array_merge($filters, $this->eq($op)),
                    'compress' => $filters[] = 'acompressor=threshold=-18dB:ratio=3:attack=20:release=250:makeup=2',
                    'limit' => $filters[] = 'alimiter=limit=0.97',
                    'exciter' => $filters[] = 'aexciter=amount='.$this->clamp((float) ($op['amount'] ?? 1), 0, 5),
                    'gain' => $filters[] = 'volume='.$this->num((float) ($op['db'] ?? 0)).'dB',
                    'normalize' => $filters[] = 'loudnorm=I='.$this->num((float) ($op['target_lufs'] ?? -16)).':TP='.$this->num((float) ($op['tp'] ?? -1.5)).':LRA=11',
                    'pitch' => $filters = array_merge($filters, $this->pitch((float) ($op['semitones'] ?? 0), $sr)),
                    'tempo' => $filters[] = 'atempo='.$this->clamp((float) ($op['factor'] ?? 1), 0.5, 2.0),
                    'silence_remove' => $filters[] = $this->silenceRemove($op),
                    'fade' => $filters[] = $this->fade($op, $finalDuration),
                    'channels' => $outFlags = array_merge($outFlags, ['-ac', ($op['layout'] ?? 'stereo') === 'mono' ? '1' : '2']),
                    'resample' => $outFlags = array_merge($outFlags, ['-ar', (string) ((int) ($op['rate'] ?? $sr))]),
                    'export' => [$outFlags, $metadata] = $this->export($op, $outFlags, $metadata),
                    default => null,
                };
            }
        }

        // Combine trim + cut into a single select expression up front.
        $select = $this->timeline($byOp['trim'][0] ?? null, $byOp['cut'] ?? [], (float) ($ctx['duration'] ?? 0));
        if ($select !== null) {
            array_unshift($filters, $select);
        }

        $args = ['-y', '-i', $input];
        $filterStr = implode(',', array_filter($filters));
        if ($filterStr !== '') {
            $args[] = '-af';
            $args[] = $filterStr;
        }
        foreach ($metadata as $k => $v) {
            $args[] = '-metadata';
            $args[] = $k.'='.$v;
        }
        $args = array_merge($args, $outFlags, [$output]);

        return ['args' => $args, 'filters' => $filterStr, 'output' => $outFlags];
    }

    /** Sound-shaping ops that can be auto-previewed over a short window. */
    public const SOUND_OPS = ['denoise', 'dehum', 'declick', 'declip', 'eq', 'compress', 'limit', 'exciter', 'gain', 'normalize', 'pitch', 'tempo'];

    /**
     * Run the full render. Returns ['ok' => bool, 'error' => ?string, 'cmd' => string].
     */
    public function render(string $input, string $output, array $ops, array $ctx = []): array
    {
        if (! is_file($input)) {
            return ['ok' => false, 'error' => 'Source audio file not found.', 'cmd' => ''];
        }

        $built = $this->buildArgs($input, $output, $ops, $ctx);

        return $this->runArgs($built['args'], $output);
    }

    /** Duration-preserving sound effects that can be baked into the main
     *  waveform preview without breaking marker/segment alignment. */
    public const PREVIEW_OPS = ['denoise', 'declick', 'declip', 'pitch', 'normalize', 'exciter'];

    /**
     * Preview render: apply the given sound effects and export a compact MP3
     * the Studio loads into the main waveform. $duration > 0 limits it to a
     * window (from $start); $duration = 0 renders the whole file so the
     * waveform stays aligned with markers/segments/transcripts.
     */
    public function renderPreview(string $input, string $output, array $ops, array $ctx, float $start = 0, float $duration = 0): array
    {
        if (! is_file($input)) {
            return ['ok' => false, 'error' => 'Source audio file not found.', 'cmd' => ''];
        }

        $sound = array_values(array_filter($ops, fn ($o) => in_array($o['op'] ?? '', self::SOUND_OPS, true)));
        $sound[] = ['op' => 'export', 'format' => 'mp3', 'bitrate' => 160];

        $built = $this->buildArgs($input, $output, $sound, $ctx);
        $args = $built['args'];
        if ($duration > 0) {
            array_splice($args, 1, 0, ['-ss', $this->num(max(0, $start)), '-t', $this->num($duration)]);
        }

        return $this->runArgs($args, $output);
    }

    /** Execute an ffmpeg argument list. */
    private function runArgs(array $args, string $output): array
    {
        if (! $this->available()) {
            return ['ok' => false, 'error' => 'ffmpeg is not available on this server (runs in the Docker image).', 'cmd' => ''];
        }

        $cmd = $this->processor->binary('ffmpeg').' '.implode(' ', array_map('escapeshellarg', $args));
        @mkdir(dirname($output), 0775, true);

        $proc = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($proc)) {
            return ['ok' => false, 'error' => 'Failed to start ffmpeg.', 'cmd' => $cmd];
        }
        stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $p) {
            fclose($p);
        }

        if (proc_close($proc) !== 0 || ! is_file($output)) {
            $tail = trim(implode("\n", array_slice(explode("\n", $stderr), -6)));

            return ['ok' => false, 'error' => 'ffmpeg failed: '.$tail, 'cmd' => $cmd];
        }

        return ['ok' => true, 'error' => null, 'cmd' => $cmd];
    }

    /* ------------------------------ op builders ----------------------- */

    private function timeline(?array $trim, array $cuts, float $duration): ?string
    {
        $parts = [];
        if ($trim !== null) {
            $parts[] = 'between(t,'.$this->num((float) $trim['start']).','.$this->num((float) $trim['end']).')';
        }
        foreach ($cuts as $cut) {
            $parts[] = 'not(between(t,'.$this->num((float) $cut['start']).','.$this->num((float) $cut['end']).'))';
        }
        if ($parts === []) {
            return null;
        }

        return "aselect='".implode('*', $parts)."',asetpts=N/SR/TB";
    }

    private function dehum(int $freq): string
    {
        $freq = in_array($freq, [50, 60], true) ? $freq : 50;

        return "bandreject=f={$freq}:width_type=h:w=6,bandreject=f=".($freq * 2).':width_type=h:w=8';
    }

    /** @return array<string> */
    private function eq(array $op): array
    {
        $f = [];
        if (isset($op['bass']) && (float) $op['bass'] != 0.0) {
            $f[] = 'bass=g='.$this->num((float) $op['bass']);
        }
        if (isset($op['mid']) && (float) $op['mid'] != 0.0) {
            $f[] = 'equalizer=f=1500:width_type=o:w=1.5:g='.$this->num((float) $op['mid']);
        }
        if (isset($op['treble']) && (float) $op['treble'] != 0.0) {
            $f[] = 'treble=g='.$this->num((float) $op['treble']);
        }

        return $f;
    }

    /** Pitch shift preserving duration WITHOUT librubberband (works on any ffmpeg). */
    private function pitch(float $semitones, int $sr): array
    {
        if ($semitones == 0.0) {
            return [];
        }
        $ratio = 2 ** ($semitones / 12);
        $newRate = (int) round($sr * $ratio);
        $tempo = $this->clamp(1 / $ratio, 0.5, 2.0);

        return ["asetrate={$newRate}", "aresample={$sr}", "atempo=".$this->num($tempo)];
    }

    private function silenceRemove(array $op): string
    {
        $th = (float) ($op['threshold_db'] ?? -40);
        $gap = (float) ($op['min_gap'] ?? 0.5);

        return 'silenceremove=start_periods=1:start_threshold='.$this->num($th).'dB:start_duration=0'
            .':stop_periods=-1:stop_threshold='.$this->num($th).'dB:stop_duration='.$this->num($gap);
    }

    private function fade(array $op, float $finalDuration): string
    {
        $d = max(0.1, (float) ($op['duration'] ?? 2));
        if (($op['dir'] ?? 'in') === 'out') {
            $st = max(0, $finalDuration - $d);

            return 'afade=t=out:st='.$this->num($st).':d='.$this->num($d);
        }

        return 'afade=t=in:st=0:d='.$this->num($d);
    }

    /** @return array{0: array<string>, 1: array<string>} [outFlags, metadata] */
    private function export(array $op, array $outFlags, array $metadata): array
    {
        $format = strtolower((string) ($op['format'] ?? 'wav'));
        $bits = (int) ($op['bit_depth'] ?? 24);
        $bitrate = (int) ($op['bitrate'] ?? 0);

        match ($format) {
            'mp3' => $outFlags = array_merge($outFlags, ['-c:a', 'libmp3lame', '-b:a', ($bitrate ?: 192).'k']),
            'aac', 'm4a' => $outFlags = array_merge($outFlags, ['-c:a', 'aac', '-b:a', ($bitrate ?: 192).'k']),
            'ogg', 'oga' => $outFlags = array_merge($outFlags, ['-c:a', 'libvorbis', '-b:a', ($bitrate ?: 192).'k']),
            'flac' => $outFlags = array_merge($outFlags, ['-c:a', 'flac']),
            default => $outFlags = array_merge($outFlags, ['-c:a', 'pcm_s'.($bits >= 24 ? 24 : 16).'le']),
        };

        foreach (($op['metadata'] ?? []) as $k => $v) {
            if ($v !== null && $v !== '') {
                $metadata[preg_replace('/[^a-z_]/', '', strtolower((string) $k))] = (string) $v;
            }
        }

        return [$outFlags, $metadata];
    }

    /* ------------------------------- helpers -------------------------- */

    private function estimateDuration(array $ops, float $source): float
    {
        $dur = $source;
        foreach ($ops as $op) {
            if (($op['op'] ?? '') === 'trim') {
                $dur = max(0, (float) $op['end'] - (float) $op['start']);
            }
        }
        foreach ($ops as $op) {
            if (($op['op'] ?? '') === 'cut') {
                $dur = max(0, $dur - ((float) $op['end'] - (float) $op['start']));
            }
        }
        foreach ($ops as $op) {
            if (($op['op'] ?? '') === 'tempo' && ! empty($op['factor'])) {
                $dur /= (float) $op['factor'];
            }
        }

        return $dur;
    }

    private function clamp(float $v, float $min, float $max): float
    {
        return round(max($min, min($max, $v)), 4);
    }

    private function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.') ?: '0';
    }
}
