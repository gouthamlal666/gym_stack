@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Expenses" subtitle="Rent, salaries, utilities, equipment and more.">
        <input type="month" wire:model.live="month" class="input w-auto">
        <button wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4"/> Add expense</button>
    </x-page-header>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="By category" subtitle="{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }} · {{ Money::format($total) }}">
            @if($byCategory->isNotEmpty())
                <x-chart height="h-56" :config="['type' => 'doughnut', 'labels' => $byCategory->keys()->map(fn ($k) => config('gym.expense_categories.'.$k))->values(), 'datasets' => [['data' => $byCategory->values()->map(fn ($v) => (float) $v), 'colors' => ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#64748b']]]]"/>
                <ul class="mt-4 space-y-1.5 text-sm">
                    @foreach($byCategory as $c => $t)
                        <li><button wire:click="$set('category', '{{ $category === $c ? '' : $c }}')" @class(['flex w-full justify-between rounded px-2 py-1 hover:bg-slate-50', 'bg-brand-50' => $category === $c])><span>{{ config("gym.expense_categories.$c") }}</span><span class="font-medium">{{ Money::format($t) }}</span></button></li>
                    @endforeach
                </ul>
            @else
                <x-empty icon="receipt" title="No expenses this month"/>
            @endif
        </x-card>
        <div class="card overflow-x-auto lg:col-span-2">
            <table class="table">
                <thead><tr><th>Date</th><th>Category</th><th>Vendor / note</th><th>Branch</th><th class="text-right">Amount</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($expenses as $e)
                    <tr>
                        <td>{{ $e->expense_date->format('d M') }}</td>
                        <td><x-badge>{{ $e->categoryLabel() }}</x-badge></td>
                        <td><div>{{ $e->vendor ?: '—' }}</div><div class="text-xs text-slate-500">{{ $e->description }}</div></td>
                        <td class="text-slate-500">{{ $e->branch?->name }}</td>
                        <td class="text-right font-medium">{{ Money::format($e->amount) }}</td>
                        <td class="text-right whitespace-nowrap">
                            <button wire:click="edit({{ $e->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4"/></button>
                            <button wire:click="delete({{ $e->id }})" wire:confirm="Delete this expense?" class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4"/></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="receipt" title="No expenses"/></td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="border-t border-slate-100 p-4">{{ $expenses->links() }}</div>
        </div>
    </div>

    <x-modal wire:model="showForm" :title="$editingId ? 'Edit expense' : 'Add expense'">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Category" name="expense_category"><select wire:model="expense_category" class="input">@foreach(config('gym.expense_categories') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
            <x-field label="Amount" name="amount"><input type="number" step="0.01" wire:model="amount" class="input"></x-field>
            <x-field label="Date" name="expense_date"><input type="date" wire:model="expense_date" class="input"></x-field>
            <x-field label="Branch" name="branch_id"><select wire:model="branch_id" class="input"><option value="">—</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></x-field>
            <x-field label="Vendor" name="vendor" class="sm:col-span-2"><input wire:model="vendor" class="input"></x-field>
            <x-field label="Description" name="description" class="sm:col-span-2"><textarea wire:model="description" rows="2" class="input"></textarea></x-field>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save</button></x-slot:footer>
    </x-modal>
</div>
