@extends('layouts.admin')

@section('title', 'Watch Clips')

@section('content')
<x-page-header title="Watch Clips" subtitle="Manage short-form vertical video clips displayed in the public Clips / Shorts feed.">
    @can('watch.manage')
        <a href="{{ route('admin.watch-clips.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Clip</a>
    @endcan
</x-page-header>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="w-full sm:max-w-sm"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search clips or creators…" class="form-input w-full"></form>
    <span class="text-sm text-slate-500 dark:text-slate-400">{{ $clips->total() }} clips</span>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
    @forelse ($clips as $clip)
        <article class="card overflow-hidden">
            {{-- 9:16 vertical preview --}}
            <div class="relative bg-slate-900" style="aspect-ratio:9/16">
                @if ($clip->thumbnail_path)
                    <img src="{{ asset('storage/'.$clip->thumbnail_path) }}" alt="" class="size-full object-cover">
                @elseif ($clip->video_path)
                    <video src="{{ asset('storage/'.$clip->video_path) }}" class="size-full object-cover" muted preload="none"></video>
                @else
                    <div class="grid size-full place-items-center text-slate-500"><x-icon name="video" class="size-12" /></div>
                @endif
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent p-4 pt-10 text-white">
                    @if ($clip->creator_name)
                        <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">{{ $clip->creator_name }}</p>
                    @endif
                    <h2 class="mt-1 font-semibold">{{ $clip->title }}</h2>
                    @if ($clip->hashtags)
                        <p class="mt-1 text-[10px] text-white/50">{{ $clip->hashtags }}</p>
                    @endif
                </div>
                @if ($clip->video_path)
                    <span class="absolute right-3 top-3 rounded-full bg-black/60 px-2 py-1 text-[10px] font-bold text-white backdrop-blur">VIDEO</span>
                @else
                    <span class="absolute right-3 top-3 rounded-full bg-amber-500/80 px-2 py-1 text-[10px] font-bold text-black backdrop-blur">NO VIDEO</span>
                @endif
            </div>
            <div class="p-4">
                <div class="flex items-center justify-between gap-3">
                    <span class="{{ $clip->is_published ? 'badge-emerald' : 'badge-slate' }}">{{ $clip->is_published ? 'Published' : 'Draft' }}</span>
                    @can('watch.manage')
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.watch-clips.edit', $clip) }}" class="btn-ghost btn-sm" title="Edit clip"><x-icon name="pencil" class="size-4" /></a>
                            <form method="POST" action="{{ route('admin.watch-clips.destroy', $clip) }}">
                                @csrf @method('DELETE')
                                <button class="btn-ghost btn-sm text-danger hover:bg-danger/10" title="Delete clip" onclick="return confirm('Delete this clip permanently?')">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>
        </article>
    @empty
        <div class="col-span-full rounded-xl border border-dashed border-slate-300 p-12 text-center text-slate-500 dark:border-slate-700">
            <x-icon name="video" class="mx-auto size-10 text-slate-400" />
            <p class="mt-3 font-semibold">No clips yet</p>
            <p class="mt-1 text-sm">Create your first short-form vertical video clip.</p>
        </div>
    @endforelse
</div>

{{ $clips->links() }}
@endsection
