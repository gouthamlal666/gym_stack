@php
    $user = auth()->user();
    $member = $user->isMember() ? $user->member : null;
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>@include('partials.head')</head>
<body class="h-full font-sans text-slate-800 antialiased" x-data="{ sidebar: false }">
<div class="min-h-full">
    {{-- Mobile backdrop --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebar = false"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 transition-transform lg:translate-x-0"
           :class="sidebar && 'translate-x-0'">
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
            @if($user->gym?->logoUrl())
                <img src="{{ $user->gym->logoUrl() }}" class="size-8 rounded-lg object-cover" alt="">
            @else
                <div class="flex size-8 items-center justify-center rounded-lg bg-brand text-white"><x-icon name="bolt" class="size-5"/></div>
            @endif
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-white">{{ $user->gym?->name ?? 'GymStack' }}</div>
                <div class="truncate text-xs text-slate-400">{{ $user->branch?->name ?? ($user->isSuperAdmin() ? 'Platform admin' : 'All branches') }}</div>
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            @include('partials.nav', ['user' => $user, 'member' => $member])
        </nav>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button class="btn-ghost -ml-2 lg:hidden" @click="sidebar = true" aria-label="Open menu"><x-icon name="menu" class="size-5"/></button>
            <h1 class="truncate text-lg font-semibold text-slate-900">{{ $title ?? '' }}</h1>
            <div class="ml-auto flex items-center gap-3" x-data="{ open: false }">
                <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 sm:inline">{{ $user->roleLabel() }}</span>
                <button @click="open = !open" class="flex items-center gap-2 rounded-full p-0.5 hover:bg-slate-100">
                    <x-avatar :name="$user->name" :src="$user->avatarUrl()" size="size-9"/>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition
                     class="absolute top-14 right-4 w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                    <div class="px-3 py-2">
                        <div class="text-sm font-medium">{{ $user->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $user->email }}</div>
                    </div>
                    <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="user" class="size-4"/> Profile & security</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><x-icon name="logout" class="size-4"/> Sign out</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</div>

{{-- Toasts --}}
<div x-data="toaster(@js(session('toast')))" class="pointer-events-none fixed right-4 bottom-4 z-50 flex w-80 flex-col gap-2">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-3.5 text-sm shadow-lg"
             :class="t.type === 'error' ? 'border-rose-200' : 'border-emerald-200'">
            <span class="mt-0.5 size-2 shrink-0 rounded-full" :class="t.type === 'error' ? 'bg-rose-500' : 'bg-emerald-500'"></span>
            <span x-text="t.message"></span>
        </div>
    </template>
</div>
@livewireScriptConfig
</body>
</html>
