@extends('layouts.admin')

@section('title', $playlist->title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">{{ $playlist->title }}</h2>
            @if ($playlist->is_public)<span class="badge-green">Public</span>@else<span class="badge-slate">Private</span>@endif
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            By {{ $playlist->user?->name ?? 'Unknown listener' }}
            · {{ $playlist->items->count() }} {{ Str::plural('track', $playlist->items->count()) }}
            · Created {{ $playlist->created_at?->format('j M Y') }}
        </p>
        @if ($playlist->description)<p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">{{ $playlist->description }}</p>@endif
    </div>
    <a href="{{ route('admin.playlists.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to Playlists</a>
</div>

<div class="card">
    <div class="card-header"><span class="text-sm font-semibold">Tracks</span></div>
    <div class="table-shell">
        <table class="table-app">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Track</th>
                    <th>Type</th>
                    <th>Kind</th>
                    <th class="text-right">Open</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($playlist->items as $item)
                    @php
                        $playable = $item->playable;
                        $isSong = $playable instanceof \App\Models\Song;
                        $asset = $isSong ? $playable->audioAsset : $playable;
                        $title = $asset?->title ?? '— (removed)';
                        $singer = $isSong ? $playable->artists->where('pivot.role', 'singer')->pluck('name')->implode(', ') : null;
                    @endphp
                    <tr>
                        <td class="text-sm tabular-nums text-slate-400">{{ $loop->iteration }}</td>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $title }}</p>
                            @if ($singer)<p class="text-xs text-slate-500 dark:text-slate-400">{{ $singer }}</p>@endif
                        </td>
                        <td><span class="badge-slate">{{ $isSong ? 'Song' : ucfirst(str_replace('_', ' ', $asset?->content_type ?? 'Audio')) }}</span></td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $asset ? ($asset->status === 'published' ? 'Published' : ucfirst($asset->status)) : '—' }}</td>
                        <td class="text-right">
                            @if ($asset)<a href="{{ route('admin.assets.show', $asset) }}" class="btn-ghost btn-sm"><x-icon name="eye" class="size-4" /></a>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="music" title="This playlist is empty" message="The listener hasn't added any tracks yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
