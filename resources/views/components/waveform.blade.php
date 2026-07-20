@props(['peaks' => [], 'height' => 40, 'class' => 'text-primary-600 dark:text-primary-400'])

@php
    $peaks = is_array($peaks) && $peaks !== [] ? $peaks : array_fill(0, 60, 0.15);
    $count = count($peaks);
    $barWidth = 3;
    $gap = 1.5;
    $width = $count * ($barWidth + $gap);
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none"
     {{ $attributes->merge(['class' => "w-full $class"]) }} style="height: {{ $height }}px">
    @foreach ($peaks as $i => $peak)
        @php $h = max(2, $peak * $height); @endphp
        <rect x="{{ $i * ($barWidth + $gap) }}" y="{{ ($height - $h) / 2 }}"
              width="{{ $barWidth }}" height="{{ $h }}" rx="1" fill="currentColor" opacity="0.85" />
    @endforeach
</svg>
