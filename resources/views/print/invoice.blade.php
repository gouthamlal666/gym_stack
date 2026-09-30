@php $gym = $invoice->gym; $m = fn ($v) => \App\Support\Money::format($v, $gym->currency_symbol); @endphp
@extends('print.layout', ['gym' => $gym])
@section('title', 'Invoice '.$invoice->number)
@section('heading')
    <div class="text-3xl font-bold tracking-tight" style="color: var(--brand)">INVOICE</div>
    <div class="mt-1 font-mono">{{ $invoice->number }}</div>
    <div class="text-sm text-slate-500">Issued {{ $invoice->issue_date->format('d M Y') }} · Due {{ $invoice->due_date->format('d M Y') }}</div>
    <div class="mt-1 text-sm font-semibold uppercase">{{ $invoice->status }}</div>
@endsection
@section('content')
    <div class="mt-6"><div class="text-xs text-slate-500 uppercase">Bill to</div><div class="font-semibold">{{ $invoice->member->name }}</div>
        <div class="text-sm text-slate-500">{{ $invoice->member->member_code }} · {{ $invoice->member->phone }} · {{ $invoice->member->email }}</div></div>
    <table class="mt-6 w-full text-sm">
        <thead><tr class="border-b border-slate-300 text-left text-xs text-slate-500 uppercase"><th class="py-2">Description</th><th class="py-2 text-right">Qty</th><th class="py-2 text-right">Price</th><th class="py-2 text-right">Amount</th></tr></thead>
        <tbody>@foreach($invoice->items as $i)<tr class="border-b border-slate-100"><td class="py-2">{{ $i->description }}</td><td class="py-2 text-right">{{ $i->quantity }}</td><td class="py-2 text-right">{{ $m($i->unit_price) }}</td><td class="py-2 text-right">{{ $m($i->amount) }}</td></tr>@endforeach</tbody>
    </table>
    <dl class="mt-4 ml-auto w-64 space-y-1 text-sm">
        <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ $m($invoice->subtotal) }}</dd></div>
        @if($invoice->discount > 0)<div class="flex justify-between"><dt>Discount</dt><dd>−{{ $m($invoice->discount) }}</dd></div>@endif
        <div class="flex justify-between"><dt>{{ $gym->tax_label }} {{ (float) $invoice->tax_rate }}%</dt><dd>{{ $m($invoice->tax_amount) }}</dd></div>
        <div class="flex justify-between border-t border-slate-300 pt-1 text-base font-bold"><dt>Total</dt><dd>{{ $m($invoice->total) }}</dd></div>
        <div class="flex justify-between"><dt>Paid</dt><dd>{{ $m($invoice->amount_paid) }}</dd></div>
        @if($invoice->amount_refunded > 0)<div class="flex justify-between"><dt>Refunded</dt><dd>−{{ $m($invoice->amount_refunded) }}</dd></div>@endif
        <div class="flex justify-between font-bold"><dt>Balance due</dt><dd>{{ $m($invoice->balance()) }}</dd></div>
    </dl>
    @if($invoice->payments->isNotEmpty())
        <div class="mt-8 text-xs text-slate-500 uppercase">Payments</div>
        <table class="mt-1 w-full text-sm">@foreach($invoice->payments as $p)<tr class="border-b border-slate-100"><td class="py-1.5">{{ $p->receipt_number }}</td><td>{{ $p->paid_at->format('d M Y') }}</td><td>{{ $p->methodLabel() }}</td><td class="text-right">{{ $p->type === 'refund' ? '−' : '' }}{{ $m($p->amount) }}</td></tr>@endforeach</table>
    @endif
    @if($invoice->notes)<p class="mt-6 text-sm text-slate-600">{{ $invoice->notes }}</p>@endif
@endsection
