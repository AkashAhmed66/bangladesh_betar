@props(['action', 'label' => 'Delete', 'confirm' => 'Delete this item? This cannot be undone.'])

<form method="POST" action="{{ $action }}" class="inline"
      x-data @submit.prevent="if (confirm(@js(__($confirm)))) $el.submit()">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn-ghost btn-sm text-rose-600 dark:text-rose-400" title="{{ __($label) }}">
        <x-icon name="trash" class="size-4" />
    </button>
</form>
