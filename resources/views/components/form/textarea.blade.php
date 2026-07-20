@props(['label' => null, 'name', 'value' => null, 'rows' => 4, 'required' => false, 'help' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @if ($required) required @endif
              {{ $attributes->merge(['class' => 'form-input']) }}>{{ old($name, $value) }}</textarea>
    @if ($help)<p class="form-help">{{ $help }}</p>@endif
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
