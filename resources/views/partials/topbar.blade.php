<header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur
               dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">

    {{-- Mobile sidebar toggle --}}
    <button @click="$store.ui.sidebarOpenMobile = true" class="btn-ghost -ml-2 p-2 lg:hidden">
        <x-icon name="menu" class="size-5" />
    </button>

    {{-- Desktop collapse toggle --}}
    <button @click="$store.ui.toggleSidebar()" class="btn-ghost hidden p-2 lg:inline-flex" title="Toggle sidebar">
        <x-icon name="menu" class="size-5" />
    </button>

    <div class="min-w-0 flex-1">
        <h1 class="truncate text-sm font-semibold text-slate-900 dark:text-white sm:text-base">@yield('title', 'Dashboard')</h1>
    </div>

    {{-- Colour mode switch --}}
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" class="btn-ghost p-2" title="Colour mode">
            <x-icon name="sun" class="size-5 dark:hidden" />
            <x-icon name="moon" class="size-5 hidden dark:block" />
        </button>
        <div x-show="open" @click.outside="open = false" x-transition.origin.top.right class="dropdown-panel w-40" x-cloak>
            <button @click="$store.ui.setMode('light'); open = false" class="dropdown-item" :class="$store.ui.mode === 'light' && 'text-primary-700 dark:text-primary-300'">
                <x-icon name="sun" class="size-4" /> Light
            </button>
            <button @click="$store.ui.setMode('dark'); open = false" class="dropdown-item" :class="$store.ui.mode === 'dark' && 'text-primary-700 dark:text-primary-300'">
                <x-icon name="moon" class="size-4" /> Dark
            </button>
            <button @click="$store.ui.setMode('system'); open = false" class="dropdown-item" :class="$store.ui.mode === 'system' && 'text-primary-700 dark:text-primary-300'">
                <x-icon name="computer" class="size-4" /> System
            </button>
        </div>
    </div>

    {{-- Notifications --}}
    @php $pendingApprovals = auth()->user()->can('approvals.view') ? \App\Models\Approval::actionableBy(auth()->user())->count() : 0; @endphp
    <a href="{{ auth()->user()->can('approvals.view') ? route('admin.approvals.index') : '#' }}" class="btn-ghost relative p-2" title="Pending approvals">
        <x-icon name="bell" class="size-5" />
        @if ($pendingApprovals > 0)
            <span class="absolute -top-0.5 -right-0.5 flex size-4.5 items-center justify-center rounded-full bg-accent-600 text-[10px] font-bold text-white">
                {{ min($pendingApprovals, 9) }}
            </span>
        @endif
    </a>

    {{-- User menu --}}
    @php $me = auth()->user(); $avatar = $me->avatarUrl(); @endphp
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-2 hover:bg-slate-100 dark:hover:bg-slate-800">
            <span class="flex size-8 items-center justify-center overflow-hidden rounded-full bg-primary-700 text-sm font-semibold text-white">
                @if ($avatar)
                    <img src="{{ $avatar }}" alt="{{ $me->name }}" class="size-full object-cover">
                @else
                    {{ strtoupper(substr($me->name, 0, 1)) }}
                @endif
            </span>
            <span class="hidden text-left sm:block">
                <span class="block max-w-32 truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $me->name }}</span>
                <span class="block max-w-32 truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $me->getRoleNames()->first() }}</span>
            </span>
            <x-icon name="chevron-down" class="hidden size-3.5 text-slate-400 sm:block" />
        </button>
        <div x-show="open" @click.outside="open = false" x-transition.origin.top.right class="dropdown-panel" x-cloak>
            <div class="border-b border-slate-100 px-3 py-2.5 dark:border-slate-700">
                <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $me->name }}</p>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $me->email }}</p>
            </div>
            <a href="{{ route('admin.profile.edit') }}" class="dropdown-item mt-1 w-full">
                <x-icon name="user-circle" class="size-4" /> My Profile
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="dropdown-item w-full text-rose-600 dark:text-rose-400">
                    <x-icon name="logout" class="size-4" /> Sign out
                </button>
            </form>
        </div>
    </div>
</header>
