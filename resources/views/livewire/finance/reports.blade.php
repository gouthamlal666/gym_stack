@php use App\Support\Money; $profit = $totals['revenue'] - $totals['expenses']; @endphp
<div class="space-y-6">
    <x-page-header title="Financial reports" subtitle="Revenue, expenses, profit and outstanding dues.">
        <button wire:click="export" class="btn-secondary"><x-icon name="document" class="size-4"/> Export CSV</button>
    </x-page-header>

    <div class="card flex flex-wrap items-center gap-3 p-4">
        @foreach(['today' => 'Today', 'month' => 'This month', 'last_month' => 'Last month', 'year' => 'This year'] as $k => $l)
            <button wire:click="preset('{{ $k }}')" class="btn-secondary btn-sm">{{ $l }}</button>
        @endforeach
        <div class="ml-auto flex flex-wrap items-center gap-2">
            <input type="date" wire:model.live="from" class="input w-auto py-1.5"><span class="text-slate-400">→</span><input type="date" wire:model.live="to" class="input w-auto py-1.5">
            <select wire:model.live="groupBy" class="input w-auto py-1.5"><option value="day">Daily</option><option value="month">Monthly</option></select>
            @if($branches->count() > 1)
                <select wire:model.live="branch" class="input w-auto py-1.5"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-4">
        <x-stat label="Revenue (net of refunds)" :value="Money::format($totals['revenue'])" icon="cash" tone="green"/>
        <x-stat label="Expenses" :value="Money::format($totals['expenses'])" icon="receipt" tone="amber"/>
        <x-stat label="Profit" :value="Money::format($profit)" :tone="$profit >= 0 ? 'blue' : 'red'" icon="chart" :hint="$totals['revenue'] > 0 ? round($profit / $totals['revenue'] * 100, 1).'% margin' : null"/>
        <x-stat label="Outstanding dues" :value="Money::format($outstanding->sum(fn ($i) => $i->balance()))" icon="clock" tone="red" :hint="$outstanding->count().' open invoices'"/>
    </div>

    <x-card title="Revenue vs expenses"><x-chart :config="$chart" height="h-72" wire:key="chart-{{ $from }}-{{ $to }}-{{ $groupBy }}-{{ $branch }}"/></x-card>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Branch-wise" :padding="false">
            <table class="table"><thead><tr><th>Branch</th><th class="text-right">Revenue</th><th class="text-right">Expenses</th><th class="text-right">Profit</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($branchReport as $b)
                    <tr><td class="font-medium">{{ $b['name'] }}</td><td class="text-right">{{ Money::format($b['revenue']) }}</td><td class="text-right">{{ Money::format($b['expenses']) }}</td>
                        <td @class(['text-right font-semibold', 'text-emerald-600' => $b['profit'] >= 0, 'text-rose-600' => $b['profit'] < 0])>{{ Money::format($b['profit']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </x-card>
        <x-card title="Revenue by source">
            @forelse($byType as $type => $amount)
                <div class="mb-3">
                    <div class="flex justify-between text-sm"><span>{{ $type }}</span><span class="font-medium">{{ Money::format($amount) }}</span></div>
                    <x-progress :value="$byType->sum() ? $amount / $byType->sum() * 100 : 0" class="mt-1"/>
                </div>
            @empty
                <x-empty icon="cash" title="No revenue in this period"/>
            @endforelse
        </x-card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card :title="$groupBy === 'month' ? 'Monthly report' : 'Daily report'" :padding="false">
            <div class="max-h-96 overflow-y-auto"><table class="table">
                <thead class="sticky top-0"><tr><th>Period</th><th class="text-right">Revenue</th><th class="text-right">Expenses</th><th class="text-right">Profit</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach(array_reverse($rows) as $r)
                    <tr><td>{{ $r['label'] }}</td><td class="text-right">{{ Money::format($r['revenue']) }}</td><td class="text-right">{{ Money::format($r['expenses']) }}</td>
                        <td @class(['text-right font-medium', 'text-rose-600' => $r['profit'] < 0])>{{ Money::format($r['profit']) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </x-card>
        <x-card title="Outstanding payments" :padding="false">
            <div class="max-h-96 overflow-y-auto"><table class="table">
                <thead class="sticky top-0"><tr><th>Invoice</th><th>Member</th><th>Due</th><th class="text-right">Balance</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($outstanding as $i)
                    <tr><td><a href="{{ route('invoices.show', $i) }}" wire:navigate class="text-brand hover:underline">{{ $i->number }}</a></td><td>{{ $i->member->name }}</td>
                        <td @class(['text-rose-600' => $i->isOverdue()])>{{ $i->due_date->format('d M') }}</td><td class="text-right font-medium">{{ Money::format($i->balance()) }}</td></tr>
                @empty
                    <tr><td colspan="4"><x-empty icon="check" title="All settled 🎉"/></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-card>
    </div>
</div>
