@php $gym = $payment->gym; $m = fn ($v) => \App\Support\Money::format($v, $gym->currency_symbol); @endphp
@extends('print.layout', ['gym' => $gym])
@section('title', 'Receipt '.$payment->receipt_number)
@section('heading')
    <div class="text-3xl font-bold tracking-tight" style="color: var(--brand)">{{ $payment->type === 'refund' ? 'REFUND' : 'RECEIPT' }}</div>
    <div class="mt-1 font-mono">{{ $payment->receipt_number }}</div>
    <div class="text-sm text-slate-500">{{ $payment->paid_at->format('d M Y, h:i A') }}</div>
@endsection
@section('content')
    <div class="mt-8 grid grid-cols-2 gap-6 text-sm">
        <div><div class="text-xs text-slate-500 uppercase">{{ $payment->type === 'refund' ? 'Refunded to' : 'Received from' }}</div><div class="font-semibold">{{ $payment->member->name }}</div><div class="text-slate-500">{{ $payment->member->member_code }}</div></div>
        <div><div class="text-xs text-slate-500 uppercase">Against invoice</div><div class="font-semibold">{{ $payment->invoice->number }}</div><div class="text-slate-500">Balance after: {{ $m($payment->invoice->balance()) }}</div></div>
        <div><div class="text-xs text-slate-500 uppercase">Method</div><div class="font-semibold">{{ $payment->methodLabel() }} {{ $payment->reference ? '· '.$payment->reference : '' }}</div></div>
        <div><div class="text-xs text-slate-500 uppercase">{{ $payment->type === 'refund' ? 'Issued' : 'Received' }} by</div><div class="font-semibold">{{ $payment->receiver?->name ?? '—' }}</div></div>
    </div>
    <div class="mt-10 rounded-xl bg-slate-50 p-6 text-center"><div class="text-sm text-slate-500">Amount</div><div class="text-4xl font-bold">{{ $m($payment->amount) }}</div></div>
    @if($payment->notes)<p class="mt-4 text-sm text-slate-600">{{ $payment->notes }}</p>@endif
    <p class="mt-10 text-center text-xs text-slate-400">Thank you for training with {{ $gym->name }}.</p>
@endsection
