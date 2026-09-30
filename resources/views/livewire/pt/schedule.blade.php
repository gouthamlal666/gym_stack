@php
$colors = ['scheduled' => 'bg-sky-50 border-sky-300 text-sky-900', 'in_progress' => 'bg-violet-50 border-violet-300 text-violet-900',
    'completed' => 'bg-emerald-50 border-emerald-300 text-emerald-900', 'cancelled' => 'bg-slate-50 border-slate-200 text-slate-400 line-through',
    'no_show' => 'bg-rose-50 border-rose-300 text-rose-900'];
@endphp
<div>
    <x-page-header title="PT schedule" :subtitle="$start->format('d M').' – '.$end->format('d M Y')">
        @if($trainers->isNotEmpty())
            <select wire:model.live="trainer" class="input w-auto"><option value="">All trainers</option>@foreach($trainers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
        @endif
        <div class="flex rounded-lg border border-slate-200 bg-white shadow-sm">
            <button wire:click="shiftWeek(-1)" class="px-2.5 py-2 hover:bg-slate-50"><x-icon name="chevron-left" class="size-4"/></button>
            <button wire:click="thisWeek" class="border-x border-slate-200 px-3 text-sm font-medium hover:bg-slate-50">Today</button>
            <button wire:click="shiftWeek(1)" class="px-2.5 py-2 hover:bg-slate-50"><x-icon name="chevron-right" class="size-4"/></button>
        </div>
        <button wire:click="openBooking" class="btn-primary"><x-icon name="plus" class="size-4"/> Book session</button>
    </x-page-header>

    {{-- Week calendar (desktop) --}}
    <div class="card hidden overflow-x-auto md:block">
        <div class="grid min-w-[900px] grid-cols-[64px_repeat(7,1fr)]">
            <div class="border-b border-slate-200"></div>
            @foreach($days as $d)
                <div @class(['border-b border-l border-slate-200 px-2 py-3 text-center', 'bg-brand-50' => $d->isToday()])>
                    <div class="text-xs font-medium text-slate-500 uppercase">{{ $d->format('D') }}</div>
                    <div @class(['text-lg font-semibold', 'text-brand' => $d->isToday()])>{{ $d->format('d') }}</div>
                </div>
            @endforeach
            @foreach($hours as $h)
                <div class="border-b border-slate-100 pt-1 pr-2 text-right text-xs text-slate-400">{{ sprintf('%02d:00', $h) }}</div>
                @foreach($days as $d)
                    @php $cell = $grid[$d->format('Y-m-d').' '.sprintf('%02d', $h)] ?? collect(); @endphp
                    <div @class(['group relative min-h-14 border-b border-l border-slate-100 p-1', 'bg-brand-50/40' => $d->isToday()])>
                        @foreach($cell as $s)
                            <a href="{{ route('pt.sessions.show', $s) }}" wire:navigate class="mb-1 block rounded-md border-l-4 px-1.5 py-1 text-xs {{ $colors[$s->status] ?? '' }}">
                                <div class="font-semibold">{{ $s->scheduled_at->format('H:i') }} {{ $s->member->first_name }}</div>
                                <div class="truncate opacity-75">{{ $s->focus ?: $s->trainer->name }}</div>
                            </a>
                        @endforeach
                        @if($cell->isEmpty() && $d->copy()->setHour($h)->isFuture())
                            <button wire:click="openBooking('{{ $d->toDateString() }}', '{{ sprintf('%02d:00', $h) }}')" class="absolute inset-1 hidden items-center justify-center rounded-md border border-dashed border-slate-300 text-xs text-slate-400 group-hover:flex">+ Book</button>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Agenda (mobile) --}}
    <div class="space-y-4 md:hidden">
        @foreach($days as $d)
            @php $daySessions = $sessions->filter(fn ($s) => $s->scheduled_at->isSameDay($d)); @endphp
            <div class="card">
                <div class="border-b border-slate-100 px-4 py-2 text-sm font-semibold {{ $d->isToday() ? 'text-brand' : '' }}">{{ $d->format('l, d M') }}</div>
                @forelse($daySessions as $s)
                    <a href="{{ route('pt.sessions.show', $s) }}" wire:navigate class="flex items-center justify-between px-4 py-2.5 text-sm"><span><b>{{ $s->scheduled_at->format('H:i') }}</b> {{ $s->member->name }}</span><x-status :value="$s->status"/></a>
                @empty
                    <div class="px-4 py-2.5 text-sm text-slate-400">No sessions</div>
                @endforelse
            </div>
        @endforeach
    </div>

    <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
        @foreach(\App\Models\PtSession::STATUSES as $k => $l)<span class="flex items-center gap-1"><span class="size-3 rounded border-l-4 {{ $colors[$k] }}"></span>{{ $l }}</span>@endforeach
    </div>

    <x-modal name="book" title="Book PT session">
        @include('pt.partials.booking-form', ['subscriptions' => $subscriptions])
        <x-slot:footer><button wire:click="book" class="btn-primary">Book session</button></x-slot:footer>
    </x-modal>
</div>
