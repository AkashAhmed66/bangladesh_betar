@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'required' => false, 'help' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ __($label) }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ old($name, $value) }}" @if ($required) required @endif
           {{ $attributes->merge(['class' => 'form-input']) }}>
    @if ($help)<p class="form-help">{{ __($help) }}</p>@endif
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
