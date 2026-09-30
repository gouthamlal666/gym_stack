@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Membership plans" subtitle="Monthly, quarterly, yearly and trial plans with discounts and freeze rules.">
        <button wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4"/> New plan</button>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse($plans as $p)
            <div @class(['card relative flex flex-col p-5', 'opacity-60' => ! $p->is_active])>
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-medium text-slate-500">{{ $p->cycleLabel() }}</div>
                        <div class="text-lg font-semibold">{{ $p->name }}</div>
                    </div>
                    @if($p->is_trial)<x-badge color="amber">Trial</x-badge>@endif
                    @unless($p->is_active)<x-badge>Inactive</x-badge>@endunless
                </div>
                <div class="mt-3 text-3xl font-semibold">{{ Money::format($p->price) }}</div>
                <div class="text-xs text-slate-500">{{ $p->duration_days }} days @if($p->admission_fee > 0)· {{ Money::format($p->admission_fee) }} admission @endif</div>
                <ul class="mt-4 flex-1 space-y-1 text-sm text-slate-600">
                    <li>• {{ $p->max_freeze_days ? $p->max_freeze_days.' freeze days' : 'No freezing' }}</li>
                    <li>• {{ $p->active_count }} active member(s)</li>
                    @if($p->description)<li class="text-slate-500">{{ $p->description }}</li>@endif
                </ul>
                <button wire:click="edit({{ $p->id }})" class="btn-secondary btn-sm mt-4">Edit</button>
            </div>
        @empty
            <div class="card sm:col-span-4"><x-empty icon="tag" title="No plans yet" text="Create your first membership plan."/></div>
        @endforelse
    </div>

    <x-modal wire:model="showForm" :title="$editingId ? 'Edit plan' : 'New plan'">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Name" name="name" class="sm:col-span-2"><input wire:model="name" class="input"></x-field>
            <x-field label="Billing cycle" name="billing_cycle">
                <select wire:model.live="billing_cycle" class="input">@foreach(config('gym.billing_cycles') as $k => $c)<option value="{{ $k }}">{{ $c['label'] }}</option>@endforeach</select>
            </x-field>
            <x-field label="Duration (days)" name="duration_days"><input type="number" wire:model="duration_days" class="input"></x-field>
            <x-field label="Price" name="price"><input type="number" step="0.01" wire:model="price" class="input"></x-field>
            <x-field label="Admission fee" name="admission_fee"><input type="number" step="0.01" wire:model="admission_fee" class="input"></x-field>
            <x-field label="Max freeze days" name="max_freeze_days"><input type="number" wire:model="max_freeze_days" class="input"></x-field>
            <div class="flex flex-col justify-end gap-2 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_trial" class="rounded text-brand"> Trial plan</label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_active" class="rounded text-brand"> Active (can be sold)</label>
            </div>
            <x-field label="Description" name="description" class="sm:col-span-2"><textarea wire:model="description" rows="2" class="input"></textarea></x-field>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save plan</button></x-slot:footer>
    </x-modal>
</div>
