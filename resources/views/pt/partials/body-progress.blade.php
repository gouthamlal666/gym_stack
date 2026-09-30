@php $ck = $assessments->count().'-'.optional($assessments->max('updated_at'))->timestamp; @endphp
{{-- Comparison table + charts. Expects $assessments, $rows, $a, $b, $weightChart, $fatChart, $measureChart --}}
@if($assessments->isEmpty())
    <div class="card"><x-empty icon="chart" title="No body assessments yet" text="The initial assessment is recorded when personal training starts."/></div>
@else
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Compare assessments" class="lg:col-span-2" :padding="false">
            <x-slot:actions>
                <select wire:model.live="compareA" class="input w-auto py-1 text-xs">@foreach($assessments as $x)<option value="{{ $x->id }}" @selected($a?->id === $x->id)>{{ $x->assessed_on->format('d M Y') }}{{ $x->is_initial ? ' (initial)' : '' }}</option>@endforeach</select>
                <span class="text-slate-400">→</span>
                <select wire:model.live="compareB" class="input w-auto py-1 text-xs">@foreach($assessments as $x)<option value="{{ $x->id }}" @selected($b?->id === $x->id)>{{ $x->assessed_on->format('d M Y') }}</option>@endforeach</select>
            </x-slot:actions>
            <table class="table">
                <thead><tr><th>Measurement</th><th class="text-right">{{ $a->assessed_on->format('d M') }}</th><th class="text-right">{{ $b->assessed_on->format('d M') }}</th><th class="text-right">Change</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($rows as $r)
                    <tr>
                        <td class="font-medium">{{ $r['label'] }}</td>
                        <td class="text-right">{{ $r['a'] !== null ? $r['a'].' '.$r['unit'] : '—' }}</td>
                        <td class="text-right">{{ $r['b'] !== null ? $r['b'].' '.$r['unit'] : '—' }}</td>
                        <td class="text-right">@if($r['change'] !== null)<x-delta :value="$r['change']" :unit="$r['unit']" :good-when="$r['better']"/>@else — @endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-card>
        <div class="space-y-6">
            <x-card title="Weight"><x-chart :config="$weightChart" height="h-44" wire:key="wc-{{ $ck }}"/></x-card>
            <x-card title="Body fat & muscle"><x-chart :config="$fatChart" height="h-44" wire:key="fc-{{ $ck }}"/></x-card>
        </div>
        <x-card title="Measurements (cm)" class="lg:col-span-3"><x-chart :config="$measureChart" height="h-64" wire:key="mc-{{ $ck }}"/></x-card>
    </div>
@endif
