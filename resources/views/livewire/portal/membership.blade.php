@php use App\Support\Money; @endphp
<div class="space-y-6">
    <x-page-header title="Membership & bills"/>
    <x-card title="Membership history" :padding="false">
        <div class="overflow-x-auto"><table class="table"><thead><tr><th>Plan</th><th>Period</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($memberships as $m)
                <tr><td class="font-medium">{{ $m->plan->name }}</td><td>{{ $m->start_date->format('d M Y') }} – {{ $m->end_date->format('d M Y') }}</td><td><x-status :value="$m->status"/></td></tr>
            @empty <tr><td colspan="3"><x-empty icon="card" title="No memberships yet"/></td></tr> @endforelse
            </tbody></table></div>
    </x-card>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Invoices" :padding="false">
            <ul class="divide-y divide-slate-100">
                @forelse($invoices as $i)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div><a href="{{ route('invoices.print', $i) }}" target="_blank" class="font-medium hover:text-brand">{{ $i->number }}</a><div class="text-xs text-slate-500">{{ $i->issue_date->format('d M Y') }} · due {{ $i->due_date->format('d M') }}</div></div>
                        <div class="text-right"><div class="font-medium">{{ Money::format($i->total) }}</div>@if($i->balance() > 0)<div class="text-xs text-rose-600">{{ Money::format($i->balance()) }} due</div>@else<x-status :value="$i->status"/>@endif</div>
                    </li>
                @empty <x-empty icon="document" title="No invoices"/> @endforelse
            </ul>
        </x-card>
        <x-card title="Payments" :padding="false">
            <ul class="divide-y divide-slate-100">
                @forelse($payments as $p)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div><a href="{{ route('payments.receipt', $p) }}" target="_blank" class="font-medium hover:text-brand">{{ $p->receipt_number }}</a><div class="text-xs text-slate-500">{{ $p->paid_at->format('d M Y') }} · {{ $p->methodLabel() }}</div></div>
                        <div @class(['font-medium', 'text-rose-600' => $p->type === 'refund'])>{{ $p->type === 'refund' ? 'Refund ' : '' }}{{ Money::format($p->amount) }}</div>
                    </li>
                @empty <x-empty icon="cash" title="No payments"/> @endforelse
            </ul>
        </x-card>
    </div>
</div>
