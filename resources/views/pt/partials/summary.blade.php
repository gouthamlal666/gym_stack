{{-- PT program summary. Expects $s = PtProgress::summary($member), $portal (bool). --}}
@php
    $sub = $s['subscription']; $goal = $s['goal'];
    $current = $goal?->currentValue();
@endphp
<div class="grid gap-6 lg:grid-cols-3">
    {{-- Program / goal --}}
    <div class="card overflow-hidden lg:col-span-2">
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-brand-700 p-6 text-white">
            <div class="text-xs font-semibold tracking-widest text-white/60 uppercase">Your PT program</div>
            @if($goal)
                <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <div class="text-2xl font-semibold">{{ $goal->title }}</div>
                        <div class="text-sm text-white/70">{{ $goal->typeLabel() }} @if($goal->target_date)· target {{ $goal->target_date->format('d M Y') }}@endif</div>
                    </div>
                    <div class="text-right"><div class="text-4xl font-bold">{{ $goal->progressPercent() }}%</div><div class="text-xs text-white/60">complete</div></div>
                </div>
                <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/15"><div class="h-3 rounded-full bg-gradient-to-r from-amber-300 to-emerald-400" style="width: {{ $goal->progressPercent() }}%"></div></div>
                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                    <div><div class="text-xs text-white/60">Starting</div><div class="text-lg font-semibold">{{ (float) $goal->start_value }} {{ $goal->unit }}</div></div>
                    <div><div class="text-xs text-white/60">Current</div><div class="text-lg font-semibold">{{ $current !== null ? $current.' '.$goal->unit : '—' }}</div></div>
                    <div><div class="text-xs text-white/60">Target</div><div class="text-lg font-semibold">{{ (float) $goal->target_value }} {{ $goal->unit }}</div></div>
                </div>
            @else
                <div class="mt-2 text-lg">No goal set yet{{ $portal ? ' — your trainer will set one with you.' : '.' }}</div>
            @endif
        </div>
        @if($s['weightChart'])
            <div class="p-5"><x-chart :config="$s['weightChart']" height="h-48"/></div>
        @endif
    </div>

    {{-- Package tracking --}}
    <div class="card p-5">
        <div class="flex items-center justify-between"><h3 class="font-semibold">PT package</h3>@if($sub)<x-status :value="$sub->status"/>@endif</div>
        @if($sub)
            <div class="mt-1 text-sm text-slate-500">{{ $sub->package->name }} · {{ $sub->total_sessions }} sessions</div>
            <div class="relative my-5 flex items-center justify-center">
                @php $pct = $sub->progressPercent(); $c = 2 * M_PI * 52; @endphp
                <svg class="size-36 -rotate-90" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="12"/>
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#f59e0b" stroke-width="12" stroke-linecap="round" stroke-dasharray="{{ $c }}" stroke-dashoffset="{{ $c * (1 - $pct / 100) }}"/>
                </svg>
                <div class="absolute text-center"><div class="text-3xl font-bold">{{ $s['remaining'] }}</div><div class="text-xs text-slate-500">remaining</div></div>
            </div>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-500">Completed</dt><dd class="font-semibold">{{ $s['completed'] }}</dd></div>
                <div><dt class="text-slate-500">Remaining</dt><dd class="font-semibold">{{ $s['remaining'] }}</dd></div>
                <div><dt class="text-slate-500">Start date</dt><dd class="font-semibold">{{ $sub->start_date->format('d M Y') }}</dd></div>
                <div><dt class="text-slate-500">Expiry</dt><dd class="font-semibold">{{ $sub->expiry_date->format('d M Y') }}</dd></div>
                <div class="col-span-2"><dt class="text-slate-500">Trainer</dt><dd class="flex items-center gap-2 font-semibold"><x-avatar :name="$sub->trainer->name" :src="$sub->trainer->user->avatarUrl()" size="size-6"/>{{ $sub->trainer->name }}</dd></div>
            </dl>
        @else
            <x-empty icon="box" title="No PT package"/>
        @endif
    </div>

    {{-- Next session --}}
    <div class="card p-5">
        <h3 class="font-semibold">{{ $s['nextSession']?->scheduled_at->isToday() ? "Today's session" : 'Next session' }}</h3>
        @if($n = $s['nextSession'])
            <div class="mt-3 text-xl font-semibold">{{ $n->focus ?: ($s['todayWorkout']?->focus ?? 'Training session') }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ $n->scheduled_at->format('l, d M') }} · {{ $n->scheduled_at->format('h:i A') }}</div>
            <div class="mt-1 text-sm text-slate-500">Trainer: {{ $n->trainer->name }}</div>
            @unless($portal)<a href="{{ route('pt.sessions.show', $n) }}" wire:navigate class="btn-primary mt-4 w-full"><x-icon name="play" class="size-4"/> {{ $n->scheduled_at->isToday() ? 'Start session' : 'Open session' }}</a>@endunless
        @else
            <p class="mt-3 text-sm text-slate-500">No upcoming session booked.</p>
        @endif
    </div>

    {{-- Body progress --}}
    <div class="card p-5">
        <h3 class="font-semibold">Body progress</h3>
        @if(count($s['bodyRows']))
            <ul class="mt-3 space-y-2.5">
                @foreach($s['bodyRows'] as $r)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">{{ $r['label'] }}</span>
                        <span><span class="font-medium">{{ $r['a'] ?? '—' }} → {{ $r['b'] ?? '—' }} {{ $r['unit'] }}</span>
                            @if($r['change'] !== null)<x-delta :value="$r['change']" :good-when="$r['better']" class="ml-1 text-xs"/>@endif</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-sm text-slate-500">No body assessments yet.</p>
        @endif
    </div>

    {{-- Next assessment / today's workout --}}
    <div class="card p-5">
        <h3 class="font-semibold">Next assessment</h3>
        <div class="mt-3 text-2xl font-semibold">{{ $s['nextAssessment']?->format('d M Y') ?? 'Not scheduled' }}</div>
        @if($s['nextAssessment'])<div class="text-sm text-slate-500">{{ $s['nextAssessment']->diffForHumans() }}</div>@endif
        <h3 class="mt-5 font-semibold">Today's workout</h3>
        @if($d = $s['todayWorkout'])
            <div class="mt-1 text-sm">{{ $d->is_rest ? '🧘 Rest / mobility' : $d->focus }} @unless($d->is_rest)<span class="text-slate-500">· {{ $d->exercises()->count() }} exercises</span>@endunless</div>
        @else
            <div class="mt-1 text-sm text-slate-500">No workout plan assigned.</div>
        @endif
    </div>
</div>
