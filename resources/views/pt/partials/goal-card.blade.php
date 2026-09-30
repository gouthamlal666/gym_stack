@php $cur = $goal->currentValue(); $pct = $goal->progressPercent(); @endphp
<div class="card p-5">
    <div class="flex items-start justify-between gap-2">
        <div><x-badge color="amber">{{ $goal->typeLabel() }}</x-badge><div class="mt-2 text-lg font-semibold">{{ $goal->title }}</div></div>
        <x-status :value="$goal->status"/>
    </div>
    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-lg bg-slate-50 p-2"><div class="text-xs text-slate-500">Starting</div><div class="font-semibold">{{ (float) $goal->start_value }} {{ $goal->unit }}</div></div>
        <div class="rounded-lg bg-brand-50 p-2"><div class="text-xs text-slate-500">Current</div><div class="font-semibold text-brand">{{ $cur !== null ? $cur.' '.$goal->unit : '—' }}</div></div>
        <div class="rounded-lg bg-slate-50 p-2"><div class="text-xs text-slate-500">Target</div><div class="font-semibold">{{ (float) $goal->target_value }} {{ $goal->unit }}</div></div>
    </div>
    <div class="mt-4 flex justify-between text-sm"><span class="text-slate-500">Progress</span><span class="font-semibold">{{ $pct }}%</span></div>
    <x-progress :value="$pct" height="h-3" class="mt-1" :color="$pct >= 100 ? 'bg-emerald-500' : 'bg-brand'"/>
    <div class="mt-2 text-xs text-slate-500">{{ $goal->start_date->format('d M Y') }} → {{ $goal->target_date?->format('d M Y') ?? 'open' }} · tracks {{ strtolower(config("gym.body_metrics.{$goal->metric}.label")) }}</div>
    {{ $slot ?? '' }}
</div>
