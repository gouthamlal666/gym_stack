@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Invoices" subtitle="Membership, PT and ad-hoc invoices with partial payments.">
        @can('billing.collect')<button wire:click="newInvoice" class="btn-primary"><x-icon name="plus" class="size-4"/> New invoice</button>@endcan
    </x-page-header>
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-stat label="Outstanding" :value="Money::format($outstanding)" icon="cash" tone="amber"/>
        <x-stat label="Overdue" :value="Money::format($overdue)" icon="clock" tone="red"/>
    </div>
    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <input wire:model.live.debounce.300ms="search" class="input max-w-xs" placeholder="Invoice # or member">
            <select wire:model.live="status" class="input w-auto">
                <option value="">All statuses</option><option value="unpaid">Unpaid</option><option value="partial">Partially paid</option>
                <option value="overdue">Overdue</option><option value="paid">Paid</option><option value="refunded">Refunded</option><option value="void">Void</option>
            </select>
        </div>
        <div class="overflow-x-auto"><table class="table">
            <thead><tr><th>Invoice</th><th>Member</th><th>Issued</th><th>Due</th><th class="text-right">Total</th><th class="text-right">Balance</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($invoices as $i)
                <tr>
                    <td><a href="{{ route('invoices.show', $i) }}" wire:navigate class="font-medium text-brand hover:underline">{{ $i->number }}</a></td>
                    <td>{{ $i->member->name }}</td>
                    <td>{{ $i->issue_date->format('d M Y') }}</td>
                    <td @class(['text-rose-600 font-medium' => $i->isOverdue()])>{{ $i->due_date->format('d M Y') }}</td>
                    <td class="text-right">{{ Money::format($i->total) }}</td>
                    <td class="text-right font-medium">{{ Money::format($i->balance()) }}</td>
                    <td><x-status :value="$i->isOverdue() ? 'unpaid' : $i->status"/>@if($i->isOverdue())<x-badge color="red">Overdue</x-badge>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="document" title="No invoices"/></td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="border-t border-slate-100 p-4">{{ $invoices->links() }}</div>
    </div>

    <x-modal wire:model="showForm" title="New invoice" max-width="max-w-2xl">
        <div class="space-y-4">
            <x-field label="Member" name="member_id">
                @if($selectedMember)
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2"><span class="font-medium">{{ $selectedMember->name }} · {{ $selectedMember->member_code }}</span><button wire:click="$set('member_id', '')" class="text-sm text-slate-500">Change</button></div>
                @else
                    <input wire:model.live.debounce.250ms="memberSearch" class="input" placeholder="Search member…">
                    @foreach($memberResults as $r)
                        <button wire:click="$set('member_id', {{ $r->id }})" class="mt-1 block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50">{{ $r->name }} <span class="text-slate-400">{{ $r->member_code }}</span></button>
                    @endforeach
                @endif
            </x-field>
            <div>
                <label class="label">Items</label>
                <div class="space-y-2">
                    @foreach($items as $i => $item)
                        <div class="flex gap-2" wire:key="item-{{ $i }}">
                            <input wire:model="items.{{ $i }}.description" class="input flex-1" placeholder="Description">
                            <input type="number" wire:model="items.{{ $i }}.quantity" class="input w-20" min="1">
                            <input type="number" step="0.01" wire:model="items.{{ $i }}.unit_price" class="input w-32" placeholder="Price">
                            @if(count($items) > 1)<button wire:click="removeItem({{ $i }})" class="btn-ghost px-2"><x-icon name="x" class="size-4"/></button>@endif
                        </div>
                        @error("items.$i.description")<p class="error">{{ $message }}</p>@enderror
                        @error("items.$i.unit_price")<p class="error">{{ $message }}</p>@enderror
                    @endforeach
                </div>
                <button wire:click="addItem" class="btn-ghost btn-sm mt-2"><x-icon name="plus" class="size-4"/> Add line</button>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <x-field label="Discount" name="discount"><input type="number" step="0.01" wire:model="discount" class="input"></x-field>
                <x-field label="Due date" name="due_date"><input type="date" wire:model="due_date" class="input"></x-field>
                <label class="mt-7 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="apply_tax" class="rounded text-brand"> Apply {{ auth()->user()->gym->tax_label }} ({{ (float) auth()->user()->gym->tax_rate }}%)</label>
            </div>
        </div>
        <x-slot:footer><button wire:click="create" class="btn-primary">Create invoice</button></x-slot:footer>
    </x-modal>
</div>
