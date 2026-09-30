@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Payments" subtitle="Receipts and refunds.">
        <input type="date" wire:model.live="from" class="input w-auto"><span class="text-slate-400">→</span><input type="date" wire:model.live="to" class="input w-auto">
        <select wire:model.live="method" class="input w-auto"><option value="">All methods</option>@foreach(config('gym.payment_methods') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
    </x-page-header>
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Collected" :value="Money::format($collected)" icon="cash" tone="green"/>
        <x-stat label="Refunded" :value="Money::format($refunded)" icon="receipt" tone="red"/>
        <x-stat label="Net" :value="Money::format($collected - $refunded)" icon="chart"/>
    </div>
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach($byMethod as $m => $t)<x-badge color="brand">{{ config("gym.payment_methods.$m") }}: {{ Money::format($t) }}</x-badge>@endforeach
    </div>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Receipt</th><th>Date</th><th>Member</th><th>Invoice</th><th>Method</th><th>By</th><th class="text-right">Amount</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($payments as $p)
                <tr>
                    <td><a href="{{ route('payments.receipt', $p) }}" target="_blank" class="font-medium text-brand hover:underline">{{ $p->receipt_number }}</a></td>
                    <td>{{ $p->paid_at->format('d M Y H:i') }}</td>
                    <td>{{ $p->member->name }}</td>
                    <td><a href="{{ route('invoices.show', $p->invoice) }}" wire:navigate class="hover:text-brand">{{ $p->invoice->number }}</a></td>
                    <td>{{ $p->methodLabel() }}</td>
                    <td class="text-slate-500">{{ $p->receiver?->name }}</td>
                    <td @class(['text-right font-medium', 'text-rose-600' => $p->type === 'refund'])>{{ $p->type === 'refund' ? '−' : '' }}{{ Money::format($p->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="cash" title="No payments in this period"/></td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 p-4">{{ $payments->links() }}</div>
    </div>
</div>
