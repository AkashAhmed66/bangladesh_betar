@props(['points' => [], 'height' => 36, 'width' => 120, 'class' => 'text-primary-500'])

@php
    // Lightweight, dependency-free trend line: an SVG polyline + soft area
    // fill derived from a numeric series. Colour comes from the parent via
    // `currentColor` (set through the `class` prop).
    $pts = array_values(array_map('floatval', is_array($points) ? $points : []));
    $n = count($pts);
    $min = $n ? min($pts) : 0.0;
    $max = $n ? max($pts) : 1.0;
    $range = ($max - $min) ?: 1.0;

    $w = (int) $width;
    $h = (int) $height;
    $pad = 3; // keep the stroke off the top/bottom edges

    $coords = [];
    foreach ($pts as $i => $v) {
        $x = $n > 1 ? ($i / ($n - 1)) * $w : 0;
        $y = $h - $pad - (($v - $min) / $range) * ($h - 2 * $pad);
        $coords[] = round($x, 2).','.round($y, 2);
    }
    $line = implode(' ', $coords);
    $area = $n > 1 ? "0,{$h} {$line} {$w},{$h}" : '';
    $gid = 'sl'.substr(md5($line.$w.$h), 0, 8);
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none"
     {{ $attributes->merge(['class' => "w-full $class"]) }} style="height: {{ $h }}px" aria-hidden="true">
    @if ($n > 1)
        <defs>
            <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="currentColor" stop-opacity="0.28" />
                <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
            </linearGradient>
        </defs>
        <polygon points="{{ $area }}" fill="url(#{{ $gid }})" stroke="none" />
        <polyline points="{{ $line }}" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"
                  vector-effect="non-scaling-stroke" />
    @endif
</svg>
