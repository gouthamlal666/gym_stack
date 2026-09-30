<div class="space-y-6">
    <x-page-header title="My PT sessions">
        @if($subscriptions->contains(fn ($s) => $s->isUsable()))<button wire:click="openBooking" class="btn-primary"><x-icon name="plus" class="size-4"/> Book a session</button>@endif
    </x-page-header>

    @foreach($subscriptions as $s)
        <div class="card flex flex-wrap items-center gap-6 p-5">
            <div class="min-w-0 flex-1"><div class="font-semibold">{{ $s->package->name }}</div><div class="text-sm text-slate-500">with {{ $s->trainer->name }} · expires {{ $s->expiry_date->format('d M Y') }}</div>
                <x-progress :value="$s->progressPercent()" class="mt-2 max-w-md" color="bg-amber-500"/></div>
            <div class="text-center"><div class="text-2xl font-semibold">{{ $s->completedSessions() }}</div><div class="text-xs text-slate-500">Completed</div></div>
            <div class="text-center"><div class="text-2xl font-semibold">{{ $s->remainingSessions() }}</div><div class="text-xs text-slate-500">Remaining</div></div>
        </div>
    @endforeach

    <x-card title="Upcoming" :padding="false">
        <ul class="divide-y divide-slate-100">
            @forelse($upcoming as $s)
                <li class="flex flex-wrap items-center gap-4 px-5 py-3">
                    <div class="w-16 text-center"><div class="text-xs text-slate-500 uppercase">{{ $s->scheduled_at->format('D') }}</div><div class="text-xl font-semibold">{{ $s->scheduled_at->format('d') }}</div></div>
                    <div class="min-w-0 flex-1"><div class="font-medium">{{ $s->focus ?: 'PT session' }}</div><div class="text-sm text-slate-500">{{ $s->scheduled_at->format('h:i A') }} · {{ $s->duration_minutes }} min · {{ $s->trainer->name }}</div></div>
                    @if($s->status === 'scheduled' && $s->scheduled_at->gt(now()->addHours($noticeHours)))
                        <button wire:click="cancelSession({{ $s->id }})" wire:confirm="Cancel this session?" class="btn-ghost btn-sm text-rose-600">Cancel</button>
                    @endif
                </li>
            @empty
                <x-empty icon="calendar" title="Nothing booked"/>
            @endforelse
        </ul>
    </x-card>

    <x-card title="Session history" :padding="false">
        <ul class="divide-y divide-slate-100">
            @forelse($history as $s)
                <li class="px-5 py-3" x-data="{ open: false }">
                    <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                        <div><div class="font-medium">{{ $s->focus ?: 'PT session' }}</div><div class="text-sm text-slate-500">{{ $s->scheduled_at->format('D d M Y, h:i A') }} · {{ $s->trainer->name }} @if($s->calories)· 🔥 {{ $s->calories }} kcal @endif</div></div>
                        <x-status :value="$s->status"/>
                    </button>
                    <div x-show="open" x-cloak class="mt-3 space-y-2">
                        @if($s->trainer_feedback)<div class="rounded-lg bg-brand-50 p-3 text-sm"><b>Trainer feedback:</b> {{ $s->trainer_feedback }}</div>@endif
                        @foreach($s->exercises as $e)
                            <div class="flex justify-between text-sm"><span>{{ $e->name }}</span><span class="text-slate-500">{{ $e->sets }}×{{ $e->reps }} @if($e->weight)@ {{ (float) $e->weight }}kg @endif @if($e->duration_minutes)· {{ $e->duration_minutes }} min @endif</span></div>
                        @endforeach
                    </div>
                </li>
            @empty
                <x-empty icon="clock" title="No past sessions"/>
            @endforelse
        </ul>
    </x-card>

    <x-modal name="book" title="Book a PT session">
        @include('pt.partials.booking-form', ['subscriptions' => $subscriptions])
        <x-slot:footer><button wire:click="book" class="btn-primary">Book</button></x-slot:footer>
    </x-modal>
</div>
