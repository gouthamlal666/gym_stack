<div class="space-y-6">
    <x-page-header :title="'Hi '.explode(' ', auth()->user()->name)[0].' 👋'" :subtitle="now()->format('l, d F Y')">
        <a href="{{ route('pt.schedule') }}" wire:navigate class="btn-primary"><x-icon name="calendar" class="size-4"/> My schedule</a>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Sessions today" :value="$todaySessions->count()" icon="calendar"/>
        <x-stat label="My PT clients" :value="$clients->count()" icon="star" tone="amber"/>
        <x-stat label="Completed this month" :value="$completedMonth" icon="check" tone="green"/>
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Today" :padding="false">
            <ul class="divide-y divide-slate-100">
                @forelse($todaySessions as $s)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <div class="w-14 text-sm font-semibold">{{ $s->scheduled_at->format('H:i') }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium">{{ $s->member->name }}</div>
                            <div class="text-xs text-slate-500">{{ $s->focus ?: 'Session' }}</div>
                        </div>
                        <x-status :value="$s->status"/>
                        <a href="{{ route('pt.sessions.show', $s) }}" wire:navigate class="btn-secondary btn-sm">Open</a>
                    </li>
                @empty
                    <x-empty icon="calendar" title="No sessions today"/>
                @endforelse
            </ul>
        </x-card>
        <x-card title="Upcoming" :padding="false">
            <ul class="divide-y divide-slate-100">
                @forelse($upcoming as $s)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="font-medium">{{ $s->member->name }}</span>
                        <span class="text-slate-500">{{ $s->scheduled_at->format('D d M, H:i') }}</span>
                    </li>
                @empty
                    <x-empty icon="calendar" title="Nothing booked yet"/>
                @endforelse
            </ul>
        </x-card>
    </div>
    <x-card title="My PT clients" :padding="false">
        <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($clients as $c)
                @php $sub = $c->activePtSubscription; @endphp
                <a href="{{ route('pt.clients.show', $c) }}" wire:navigate class="flex items-center gap-3 bg-white p-4 hover:bg-slate-50">
                    <x-avatar :name="$c->name" :src="$c->photoUrl()"/>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ $c->name }}</div>
                        @if($sub)
                            <div class="text-xs text-slate-500">{{ $sub->remainingSessions() }} of {{ $sub->total_sessions }} sessions left</div>
                            <x-progress :value="$sub->progressPercent()" class="mt-1.5"/>
                        @else
                            <div class="text-xs text-slate-400">No active package</div>
                        @endif
                    </div>
                </a>
            @empty
                <div class="bg-white sm:col-span-3"><x-empty icon="star" title="No PT clients assigned yet"/></div>
            @endforelse
        </div>
    </x-card>
</div>
