@php use App\Support\Money; @endphp
<div>
    <x-page-header title="PT packages" subtitle="Session bundles sold to personal training customers.">
        <button wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4"/> New package</button>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse($packages as $p)
            <div @class(['card relative overflow-hidden p-5', 'opacity-60' => ! $p->is_active])>
                <div class="absolute top-0 right-0 h-20 w-20 translate-x-6 -translate-y-6 rounded-full bg-amber-100"></div>
                <div class="relative">
                    <div class="text-lg font-semibold">{{ $p->name }}</div>
                    <div class="mt-3 flex items-baseline gap-1"><span class="text-4xl font-bold">{{ $p->sessions_count }}</span><span class="text-slate-500">sessions</span></div>
                    <div class="mt-1 text-2xl font-semibold text-brand">{{ Money::format($p->price) }}</div>
                    <div class="text-xs text-slate-500">{{ Money::format($p->pricePerSession()) }} / session</div>
                    <ul class="mt-4 space-y-1 text-sm text-slate-600">
                        <li>⏱ {{ $p->session_minutes }} min sessions</li>
                        <li>📅 Valid {{ $p->validity_days }} days</li>
                        <li>👥 {{ $p->active_count }} active client(s)</li>
                    </ul>
                    <button wire:click="edit({{ $p->id }})" class="btn-secondary btn-sm mt-4 w-full">Edit</button>
                </div>
            </div>
        @empty
            <div class="card sm:col-span-4"><x-empty icon="box" title="No PT packages yet"/></div>
        @endforelse
    </div>
    <x-modal wire:model="showForm" :title="$editingId ? 'Edit package' : 'New PT package'">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Name" name="name" class="sm:col-span-2"><input wire:model="name" class="input" placeholder="e.g. Transformation 20"></x-field>
            <x-field label="Number of sessions" name="sessions_count"><input type="number" wire:model="sessions_count" class="input"></x-field>
            <x-field label="Package price" name="price"><input type="number" step="0.01" wire:model="price" class="input"></x-field>
            <x-field label="Validity (days)" name="validity_days"><input type="number" wire:model="validity_days" class="input"></x-field>
            <x-field label="Session length (min)" name="session_minutes"><input type="number" wire:model="session_minutes" class="input"></x-field>
            <x-field label="Description" name="description" class="sm:col-span-2"><textarea wire:model="description" rows="2" class="input"></textarea></x-field>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded text-brand"> Available for sale</label>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save</button></x-slot:footer>
    </x-modal>
</div>
