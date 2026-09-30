<div>
    <x-page-header title="Trainers" subtitle="Profiles, specializations, availability and commission.">
        <button wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4"/> Add trainer</button>
    </x-page-header>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($trainers as $t)
            <div @class(['card p-5', 'opacity-60' => $t->status !== 'active'])>
                <div class="flex items-start gap-4">
                    <x-avatar :name="$t->name" :src="$t->user->avatarUrl()" size="size-14"/>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('trainers.show', $t) }}" wire:navigate class="font-semibold hover:text-brand">{{ $t->name }}</a>
                        <div class="text-sm text-slate-500">{{ $t->experience_years }} yrs · {{ $t->branch?->name ?? 'All branches' }}</div>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @if($t->is_pt_trainer)<x-badge color="amber">PT</x-badge>@endif
                            @foreach(array_slice($t->specializations ?? [], 0, 3) as $s)<x-badge>{{ $s }}</x-badge>@endforeach
                        </div>
                    </div>
                    <button wire:click="edit({{ $t->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4"/></button>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 text-center">
                    <div><div class="text-lg font-semibold">{{ $t->active_clients }}</div><div class="text-xs text-slate-500">PT clients</div></div>
                    <div><div class="text-lg font-semibold">{{ $t->sessions_month }}</div><div class="text-xs text-slate-500">Sessions / mo</div></div>
                    <div><div class="text-lg font-semibold">{{ (float) $t->commission_rate }}%</div><div class="text-xs text-slate-500">Commission</div></div>
                </div>
            </div>
        @empty
            <div class="card md:col-span-3"><x-empty icon="whistle" title="No trainers yet"/></div>
        @endforelse
    </div>

    <x-modal wire:model="showForm" :title="$editingId ? 'Edit trainer' : 'Add trainer'" max-width="max-w-3xl">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-field label="Full name" name="name"><input wire:model="name" class="input"></x-field>
            <x-field label="Email (login)" name="email"><input type="email" wire:model="email" class="input"></x-field>
            <x-field label="Phone" name="phone"><input wire:model="phone" class="input"></x-field>
            <x-field label="Branch" name="branch_id"><select wire:model="branch_id" class="input"><option value="">All</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></x-field>
            <x-field label="Experience (years)" name="experience_years"><input type="number" wire:model="experience_years" class="input"></x-field>
            <x-field label="PT commission %" name="commission_rate"><input type="number" step="0.5" wire:model="commission_rate" class="input"></x-field>
            <x-field label="Specializations (comma-separated)" name="specializations" class="sm:col-span-3"><input wire:model="specializations" class="input" placeholder="Fat loss, Strength, Mobility"></x-field>
            <x-field label="Certifications (comma-separated)" name="certifications" class="sm:col-span-3"><input wire:model="certifications" class="input" placeholder="ACE CPT, K11 Nutrition"></x-field>
            <x-field label="Bio" name="bio" class="sm:col-span-3"><textarea wire:model="bio" rows="2" class="input"></textarea></x-field>
            <div class="sm:col-span-3">
                <label class="label">Weekly availability (used for PT slot booking)</label>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach(config('gym.weekdays') as $d => $day)
                        <div class="flex items-center gap-2 rounded-lg border border-slate-200 p-2">
                            <label class="flex w-28 items-center gap-2 text-sm"><input type="checkbox" wire:model.live="availability.{{ $d }}.on" class="rounded text-brand"> {{ $day }}</label>
                            @if($availability[$d]['on'] ?? false)
                                <input type="time" wire:model="availability.{{ $d }}.start" class="input py-1"><span>–</span><input type="time" wire:model="availability.{{ $d }}.end" class="input py-1">
                            @else <span class="text-xs text-slate-400">Off</span> @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center gap-6 text-sm sm:col-span-3">
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_pt_trainer" class="rounded text-brand"> Offers personal training</label>
                <select wire:model="status" class="input w-auto"><option value="active">Active</option><option value="inactive">Inactive</option></select>
            </div>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save trainer</button></x-slot:footer>
    </x-modal>
</div>
