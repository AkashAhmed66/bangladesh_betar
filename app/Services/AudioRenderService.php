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
        'declip', 'declick', 'denoise', 'dehum', 'gate', 'deesser',
        'eq', 'compress', 'limit', 'exciter',
        'gain', 'normalize',
        'pitch', 'tempo',
        'silence_remove', 'fade', 'silence',
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
        // Region-scoped effects (silence / volume / fade over a selection). They
        // reference ORIGINAL time, so they must run BEFORE trim/cut shift the
        // timeline — collected here and prepended ahead of the select filter.
        $regionFilters = [];
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
                    'gate' => $filters[] = $this->gate($op),
                    'deesser' => $filters[] = 'deesser=i='.$this->clamp((float) ($op['intensity'] ?? 0.4), 0, 1),
                    'eq' => $filters = array_merge($filters, $this->eq($op)),
                    'compress' => $filters[] = 'acompressor=threshold=-18dB:ratio=3:attack=20:release=250:makeup=2',
                    'limit' => $filters[] = 'alimiter=limit=0.97',
                    'exciter' => $filters[] = 'aexciter=amount='.$this->clamp((float) ($op['amount'] ?? 1), 0, 5),
                    'gain' => isset($op['start'], $op['end'])
                        ? $regionFilters[] = $this->regionVolume((float) $op['start'], (float) $op['end'], $this->num(10 ** ((float) ($op['db'] ?? 0) / 20)))
                        : $filters[] = 'volume='.$this->num((float) ($op['db'] ?? 0)).'dB',
                    'normalize' => $filters[] = 'loudnorm=I='.$this->num((float) ($op['target_lufs'] ?? -16)).':TP='.$this->num((float) ($op['tp'] ?? -1.5)).':LRA=11',
                    'pitch' => $filters = array_merge($filters, $this->pitch((float) ($op['semitones'] ?? 0), $sr)),
                    'tempo' => $filters[] = 'atempo='.$this->clamp((float) ($op['factor'] ?? 1), 0.5, 2.0),
                    'silence_remove' => $filters[] = $this->silenceRemove($op),
                    'silence' => isset($op['start'], $op['end'])
                        ? $regionFilters[] = $this->regionVolume((float) $op['start'], (float) $op['end'], '0')
                        : null,
                    'fade' => isset($op['start'], $op['end'])
                        ? $regionFilters[] = $this->regionFade((string) ($op['dir'] ?? 'in'), (float) $op['start'], (float) $op['end'], (string) ($op['curve'] ?? 'linear'))
                        : $filters[] = $this->fade($op, $finalDuration),
                    'channels' => $outFlags = array_merge($outFlags, ['-ac', ($op['layout'] ?? 'stereo') === 'mono' ? '1' : '2']),
                    'resample' => $outFlags = array_merge($outFlags, ['-ar', (string) ((int) ($op['rate'] ?? $sr))]),
                    'export' => [$outFlags, $metadata] = $this->export($op, $outFlags, $metadata),
                    default => null,
                };
            }
        }

        // Combine trim + cut into a single select expression.
        $select = $this->timeline($byOp['trim'][0] ?? null, $byOp['cut'] ?? [], (float) ($ctx['duration'] ?? 0));
        // Region effects run in ORIGINAL time, then the structural select (which
        // shifts the timeline), then the remaining global filters.
        $filters = array_merge($regionFilters, $select !== null ? [$select] : [], $filters);

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
    public const SOUND_OPS = ['denoise', 'dehum', 'gate', 'deesser', 'declick', 'declip', 'eq', 'compress', 'limit', 'exciter', 'gain', 'normalize', 'pitch', 'tempo'];

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
    public const PREVIEW_OPS = ['denoise', 'declick', 'declip', 'gate', 'deesser', 'pitch', 'normalize', 'exciter'];

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

        // Preview renders the FULL edit chain (incl. cut/crop) so the waveform
        // shows the live edited result. Export is re-added as a compact MP3.
        $sound = array_values(array_filter($ops, fn ($o) => ($o['op'] ?? '') !== 'export'));
        $sound[] = ['op' => 'export', 'format' => 'mp3', 'bitrate' => 160];

        $built = $this->buildArgs($input, $output, $sound, $ctx);
        $args = $built['args'];
        if ($duration > 0) {
            array_splice($args, 1, 0, ['-ss', $this->num(max(0, $start)), '-t', $this->num($duration)]);
        }

        return $this->runArgs($args, $output);
    }

    /**
     * Measure EBU R128 loudness (integrated LUFS, LRA, true-peak) and a
     * short-term loudness curve over time, by running ffmpeg's ebur128 filter
     * and parsing its log. Read-only — writes nothing.
     *
     * @return array{ok: bool, error?: string, integrated?: ?float, lra?: ?float,
     *   true_peak?: ?float, momentary_max?: ?float, short_term_max?: ?float,
     *   curve?: array<int, array{0: float, 1: float}>}
     */
    public function measureLoudness(string $input): array
    {
        if (! is_file($input)) {
            return ['ok' => false, 'error' => 'Source audio file not found.'];
        }
        if (! $this->available()) {
            return ['ok' => false, 'error' => 'ffmpeg is not available on this server.'];
        }

        $args = ['-hide_banner', '-nostats', '-i', $input, '-af', 'ebur128=peak=true', '-f', 'null', '-'];
        $cmd = $this->processor->binary('ffmpeg').' '.implode(' ', array_map('escapeshellarg', $args));

        // ebur128 logs a measurement every 100 ms, so a long file prints megabytes
        // to stderr. Capture it to a FILE (not a pipe) so a full pipe buffer can
        // never deadlock ffmpeg while we wait for it to finish.
        $errFile = tempnam(sys_get_temp_dir(), 'ebur');
        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', $errFile, 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (! is_resource($proc)) {
            @unlink($errFile);

            return ['ok' => false, 'error' => 'Failed to start ffmpeg.'];
        }
        proc_close($proc); // blocks until ffmpeg finishes; stderr is in $errFile

        $log = @file_get_contents($errFile) ?: '';
        @unlink($errFile);

        return $this->parseEbur128($log);
    }

    /** Parse ebur128 per-frame lines + the Summary block into numbers + a curve. */
    private function parseEbur128(string $log): array
    {
        $floor = -70.0;
        $toNum = static function (string $s) use ($floor): float {
            if (! is_numeric($s)) {
                return $floor;
            }

            return max($floor, (float) $s);
        };

        // Per-frame short-term loudness curve: "t: <sec> ... M: <m> ... S: <s>".
        $curve = [];
        $mMax = $floor;
        $sMax = $floor;
        if (preg_match_all('/\bt:\s*([\d.]+).*?\bM:\s*(-?[\d.a-z]+).*?\bS:\s*(-?[\d.a-z]+)/i', $log, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $t = (float) $row[1];
                $mv = $toNum($row[2]);
                $sv = $toNum($row[3]);
                $mMax = max($mMax, $mv);
                $sMax = max($sMax, $sv);
                $curve[] = [round($t, 2), round($sv, 1)];
            }
        }

        // Down-sample the curve to at most ~600 points for a light payload.
        $curve = $this->decimate($curve, 600);

        // Summary block: integrated I, LRA, true Peak.
        $summary = ($pos = strrpos($log, 'Summary:')) !== false ? substr($log, $pos) : $log;
        $grab = static function (string $re) use ($summary): ?float {
            return preg_match($re, $summary, $mm) ? (float) $mm[1] : null;
        };

        return [
            'ok' => true,
            'integrated' => $grab('/\bI:\s*(-?[\d.]+)\s*LUFS/'),
            'lra' => $grab('/\bLRA:\s*(-?[\d.]+)\s*LU\b/'),
            'true_peak' => $grab('/\bPeak:\s*(-?[\d.]+)\s*dBFS/'),
            'momentary_max' => $mMax > $floor ? round($mMax, 1) : null,
            'short_term_max' => $sMax > $floor ? round($sMax, 1) : null,
            'curve' => $curve,
        ];
    }

    /** Keep at most $max evenly-spaced points from a series. */
    private function decimate(array $points, int $max): array
    {
        $n = count($points);
        if ($n <= $max) {
            return $points;
        }
        $step = $n / $max;
        $out = [];
        for ($i = 0; $i < $max; $i++) {
            $out[] = $points[(int) floor($i * $step)];
        }

        return $out;
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
        $curve = $this->afadeCurve((string) ($op['curve'] ?? 'linear'));
        $c = $curve !== '' ? ':curve='.$curve : '';
        if (($op['dir'] ?? 'in') === 'out') {
            $st = max(0, $finalDuration - $d);

            return 'afade=t=out:st='.$this->num($st).':d='.$this->num($d).$c;
        }

        return 'afade=t=in:st=0:d='.$this->num($d).$c;
    }

    /** Map a friendly fade-curve name to an ffmpeg afade curve (empty = linear default). */
    private function afadeCurve(string $name): string
    {
        return match ($name) {
            'exp' => 'exp',
            'log' => 'log',
            'scurve' => 'hsin',   // half-sine ≈ smooth S-curve
            default => '',        // linear = afade's default ('tri')
        };
    }

    /** Noise gate: threshold given in dB, converted to ffmpeg agate's linear scale. */
    private function gate(array $op): string
    {
        $db = (float) ($op['threshold_db'] ?? -45);
        $lin = $this->clamp(10 ** ($db / 20), 0.0001, 1);

        return 'agate=threshold='.$this->num($lin).':ratio=2:attack=10:release=200:knee=2.83';
    }

    /**
     * A time-scoped volume envelope: apply $expr inside [s,e], leave the rest
     * unchanged (×1). Powers region silence / volume / gain / fades — all in
     * original time, evaluated per frame.
     */
    private function regionVolume(float $s, float $e, string $expr): string
    {
        $s = max(0, $s);
        $e = max($s, $e);

        return "volume=eval=frame:volume='if(between(t,".$this->num($s).','.$this->num($e).'),'.$expr.",1)'";
    }

    /**
     * A fade envelope over the selection only (audio outside is unchanged). The
     * curve shapes the ramp: linear, exp (slow-in), log (fast-in) or scurve
     * (smoothstep). Progress is mirrored for fade-out so the shape matches.
     */
    private function regionFade(string $dir, float $s, float $e, string $curve = 'linear'): string
    {
        $s = max(0, $s);
        $d = max(0.001, $e - $s);
        // p = progress 0..1 within the region; mirror for fade-out.
        $p = '((t-'.$this->num($s).')/'.$this->num($d).')';
        $x = $dir === 'out' ? '(1-'.$p.')' : $p;
        $ramp = match ($curve) {
            'exp' => $x.'*'.$x,                    // concave — slow start
            'log' => 'sqrt('.$x.')',               // convex — fast start
            'scurve' => $x.'*'.$x.'*(3-2*'.$x.')', // smoothstep S-curve
            default => $x,                          // linear
        };

        return $this->regionVolume($s, $e, $ramp);
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
