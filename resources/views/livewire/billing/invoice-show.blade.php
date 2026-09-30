@php use App\Support\Money; $i = $invoice; @endphp
<div class="mx-auto max-w-5xl">
    <x-page-header :title="'Invoice '.$i->number" :back="route('invoices.index')">
        <a href="{{ route('invoices.print', $i) }}" target="_blank" class="btn-secondary"><x-icon name="printer" class="size-4"/> Print / PDF</a>
        @if(in_array($i->status, ['unpaid', 'partial']))
            <button wire:click="remind" class="btn-secondary">Send reminder</button>
            @can('billing.collect')<button wire:click="openPayment" class="btn-primary"><x-icon name="cash" class="size-4"/> Collect payment</button>@endcan
        @endif
        @can('billing.refund')
            @if($i->netPaid() > 0)<button wire:click="openRefund" class="btn-secondary text-rose-600">Refund</button>@endif
            @if($i->status === 'unpaid' && (float) $i->amount_paid == 0)<button wire:click="void" wire:confirm="Void this invoice?" class="btn-ghost text-rose-600">Void</button>@endif
        @endcan
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="flex flex-wrap justify-between gap-4">
                <div>
                    <div class="text-xs tracking-wide text-slate-500 uppercase">Billed to</div>
                    <a href="{{ route('members.show', $i->member) }}" wire:navigate class="font-semibold hover:text-brand">{{ $i->member->name }}</a>
                    <div class="text-sm text-slate-500">{{ $i->member->member_code }} · {{ $i->member->phone }}</div>
                </div>
                <div class="text-right text-sm">
                    <div><x-status :value="$i->status"/> @if($i->isOverdue())<x-badge color="red">Overdue</x-badge>@endif</div>
                    <div class="mt-1 text-slate-500">Issued {{ $i->issue_date->format('d M Y') }}</div>
                    <div class="text-slate-500">Due {{ $i->due_date->format('d M Y') }}</div>
                </div>
            </div>
            <table class="table mt-6">
                <thead><tr><th>Description</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($i->items as $item)
                    <tr><td>{{ $item->description }}</td><td class="text-right">{{ $item->quantity }}</td><td class="text-right">{{ Money::format($item->unit_price) }}</td><td class="text-right">{{ Money::format($item->amount) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            <dl class="mt-4 ml-auto w-full max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ Money::format($i->subtotal) }}</dd></div>
                @if($i->discount > 0)<div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd>−{{ Money::format($i->discount) }}</dd></div>@endif
                <div class="flex justify-between"><dt class="text-slate-500">{{ auth()->user()->gym->tax_label }} ({{ (float) $i->tax_rate }}%)</dt><dd>{{ Money::format($i->tax_amount) }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-1.5 text-base font-semibold"><dt>Total</dt><dd>{{ Money::format($i->total) }}</dd></div>
                <div class="flex justify-between text-emerald-700"><dt>Paid</dt><dd>{{ Money::format($i->amount_paid) }}</dd></div>
                @if($i->amount_refunded > 0)<div class="flex justify-between text-violet-700"><dt>Refunded</dt><dd>−{{ Money::format($i->amount_refunded) }}</dd></div>@endif
                <div class="flex justify-between text-base font-semibold"><dt>Balance due</dt><dd @class(['text-rose-600' => $i->balance() > 0])>{{ Money::format($i->balance()) }}</dd></div>
            </dl>
            @if($i->notes)<p class="mt-4 text-sm text-slate-500">{{ $i->notes }}</p>@endif
        </div>

        <x-card title="Payments & refunds" :padding="false">
            <ul class="divide-y divide-slate-100">
                @forelse($i->payments as $p)
                    <li class="px-5 py-3">
                        <div class="flex justify-between">
                            <a href="{{ route('payments.receipt', $p) }}" target="_blank" class="text-sm font-medium hover:text-brand">{{ $p->receipt_number }}</a>
                            <span @class(['font-semibold', 'text-emerald-600' => $p->type === 'payment', 'text-rose-600' => $p->type === 'refund'])>{{ $p->type === 'refund' ? '−' : '' }}{{ Money::format($p->amount) }}</span>
                        </div>
                        <div class="text-xs text-slate-500">{{ $p->paid_at->format('d M Y H:i') }} · {{ $p->methodLabel() }} @if($p->reference)· {{ $p->reference }}@endif · {{ $p->receiver?->name }}</div>
                        @if($p->notes)<div class="text-xs text-slate-500 italic">{{ $p->notes }}</div>@endif
                    </li>
                @empty
                    <x-empty icon="cash" title="No payments yet"/>
                @endforelse
            </ul>
        </x-card>
    </div>

    <x-modal name="pay" title="Collect payment">
        <div class="space-y-4">
            <p class="text-sm text-slate-500">Balance due: <b>{{ Money::format($i->balance()) }}</b>. Partial payments are allowed.</p>
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Amount" name="amount"><input type="number" step="0.01" wire:model="amount" class="input"></x-field>
                <x-field label="Method" name="method"><select wire:model="method" class="input">@foreach(config('gym.payment_methods') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
            </div>
            <x-field label="Reference (UPI / card txn ID)" name="reference"><input wire:model="reference" class="input"></x-field>
            <x-field label="Notes" name="notes"><input wire:model="notes" class="input"></x-field>
        </div>
        <x-slot:footer><button wire:click="pay" class="btn-primary">Record payment</button></x-slot:footer>
    </x-modal>

    <x-modal name="refund" title="Issue refund">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Amount" name="amount"><input type="number" step="0.01" wire:model="amount" class="input"></x-field>
                <x-field label="Method" name="method"><select wire:model="method" class="input">@foreach(config('gym.payment_methods') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
            </div>
            <x-field label="Reason" name="notes"><input wire:model="notes" class="input"></x-field>
        </div>
        <x-slot:footer><button wire:click="refund" class="btn-danger">Refund</button></x-slot:footer>
    </x-modal>
</div>
