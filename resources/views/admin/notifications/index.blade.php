@extends('layouts.admin')

@section('title', 'Notifications')

@php
    // Event → [icon, literal colour classes] (literal so Tailwind compiles them).
    $amber = 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400';
    $green = 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400';
    $red = 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400';
    $blue = 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400';
    $slate = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
    $eventMeta = [
        'needs_approval' => ['clipboard-check', $amber],
        'stage_advanced' => ['arrow-path', $blue],
        'approved' => ['check-badge', $green],
        'rejected' => ['x', $red],
        'changes_requested' => ['arrow-path', $amber],
        'ai_pending' => ['shield', $amber],
        'ai_approved' => ['shield-check', $green],
        'ai_rejected' => ['shield', $red],
        'rights_submitted' => ['scale', $amber],
        'rights_status' => ['scale', $blue],
        'publish_ready' => ['globe', $green],
        'speech_ready' => ['megaphone', $green],
    ];
@endphp

@section('content')
<x-page-header title="Notifications" subtitle="Approval stages, AI moderation and rights events that involve you">
    @if (auth()->user()->unreadNotifications()->count() > 0)
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf
            <button type="submit" class="btn-secondary"><x-icon name="check-badge" class="size-4" /> Mark all read</button>
        </form>
    @endif
</x-page-header>

<div class="card">
    @forelse ($notifications as $n)
        @php [$icon, $colorClasses] = $eventMeta[$n->data['event'] ?? ''] ?? ['bell', $slate]; @endphp
        <a href="{{ route('admin.notifications.open', $n->id) }}"
           class="flex items-start gap-3 border-b border-slate-100 px-5 py-3.5 transition last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60 {{ $n->read_at ? 'opacity-60' : '' }}">
            <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full {{ $colorClasses }}">
                <x-icon :name="$icon" class="size-4.5" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $n->data['title'] ?? 'Notification' }}</span>
                    @unless ($n->read_at)<span class="size-2 rounded-full bg-accent-600"></span>@endunless
                </span>
                <span class="mt-0.5 block text-sm text-slate-600 dark:text-slate-300">{{ $n->data['message'] ?? '' }}</span>
                <span class="mt-1 block text-xs text-slate-400">{{ $n->created_at->diffForHumans() }} · {{ $n->created_at->format('j M Y H:i') }}</span>
            </span>
            <x-icon name="chevron-right" class="mt-2 size-4 shrink-0 text-slate-300 dark:text-slate-600" />
        </a>
    @empty
        <div class="px-5 py-10"><x-empty-state icon="bell" title="No notifications" message="Approval, moderation and rights events that involve you will appear here." /></div>
    @endforelse

    @if ($notifications->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
