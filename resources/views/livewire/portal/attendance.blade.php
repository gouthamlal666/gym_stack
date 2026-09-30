<div class="space-y-6">
    <x-page-header title="Attendance"/>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Current streak" :value="$streak.' day'.($streak === 1 ? '' : 's')" icon="fire" tone="amber"/>
        <x-stat label="Last 12 weeks" :value="$byDay->sum().' visits'" icon="calendar"/>
        <x-stat label="All-time visits" :value="$total" icon="check" tone="green"/>
    </div>
    <x-card title="Last 12 weeks">
        <div class="flex gap-1 overflow-x-auto">
            @for($w = 0; $w < 12; $w++)
                <div class="flex flex-col gap-1">
                    @for($d = 0; $d < 7; $d++)
                        @php $day = $start->copy()->addDays($w * 7 + $d); $c = $byDay[$day->toDateString()] ?? 0; @endphp
                        <div title="{{ $day->format('D d M') }}{{ $c ? ' · visited' : '' }}" @class(['size-4 rounded-sm', 'bg-brand' => $c, 'bg-slate-100' => ! $c && $day->lte(today()), 'bg-transparent' => $day->gt(today())])></div>
                    @endfor
                </div>
            @endfor
        </div>
    </x-card>
    <x-card title="Recent visits" :padding="false">
        <ul class="divide-y divide-slate-100">
            @forelse($recent as $a)
                <li class="flex justify-between px-5 py-2.5 text-sm"><span>{{ $a->check_in_at->format('D, d M Y') }}</span><span class="text-slate-500">{{ $a->check_in_at->format('H:i') }}{{ $a->check_out_at ? ' – '.$a->check_out_at->format('H:i') : '' }}</span></li>
            @empty <x-empty icon="check" title="No visits yet"/> @endforelse
        </ul>
    </x-card>
</div>
