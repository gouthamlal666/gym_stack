<div>
    <x-page-header title="Branches" subtitle="Manage multiple locations under one gym.">
        <button wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4"/> Add branch</button>
    </x-page-header>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($branches as $b)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div><div class="font-semibold">{{ $b->name }} @if($b->code)<span class="text-xs text-slate-400">{{ $b->code }}</span>@endif</div>
                        <div class="text-sm text-slate-500">{{ $b->address }}</div></div>
                    <button wire:click="edit({{ $b->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4"/></button>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <x-status :value="$b->is_active ? 'active' : 'inactive'"/>
                    <x-badge>{{ $b->members_count }} members</x-badge>
                    <x-badge>{{ $b->staff_count }} staff</x-badge>
                    @if($b->opens_at)<x-badge color="blue">{{ substr($b->opens_at, 0, 5) }}–{{ substr($b->closes_at, 0, 5) }}</x-badge>@endif
                </div>
            </div>
        @endforeach
    </div>
    <x-modal wire:model="showForm" :title="$editingId ? 'Edit branch' : 'Add branch'">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Name" name="name"><input wire:model="name" class="input"></x-field>
            <x-field label="Code" name="code"><input wire:model="code" class="input"></x-field>
            <x-field label="Phone" name="phone"><input wire:model="phone" class="input"></x-field>
            <x-field label="Email" name="email"><input wire:model="email" class="input"></x-field>
            <x-field label="Opens" name="opens_at"><input type="time" wire:model="opens_at" class="input"></x-field>
            <x-field label="Closes" name="closes_at"><input type="time" wire:model="closes_at" class="input"></x-field>
            <x-field label="Address" name="address" class="sm:col-span-2"><textarea wire:model="address" rows="2" class="input"></textarea></x-field>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded text-brand"> Active</label>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save</button></x-slot:footer>
    </x-modal>
</div>
