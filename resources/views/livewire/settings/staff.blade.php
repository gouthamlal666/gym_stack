<div class="space-y-6">
    <x-page-header title="Staff & roles" subtitle="Invite staff and control what each role can do.">
        <button wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4"/> Invite staff</button>
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>Role</th><th>Branch</th><th>Last sign-in</th><th>2FA</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($staff as $u)
                <tr>
                    <td><div class="flex items-center gap-3"><x-avatar :name="$u->name" :src="$u->avatarUrl()" size="size-8"/><div><div class="font-medium">{{ $u->name }}</div><div class="text-xs text-slate-500">{{ $u->email }}</div></div></div></td>
                    <td><x-badge color="brand">{{ $u->roleLabel() }}</x-badge></td>
                    <td>{{ $u->branch?->name ?? 'All' }}</td>
                    <td class="text-slate-500">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                    <td>{{ $u->two_factor_enabled ? '✅' : '—' }}</td>
                    <td><x-status :value="$u->is_active ? 'active' : 'inactive'"/></td>
                    <td class="text-right"><button wire:click="edit({{ $u->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4"/></button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <x-card title="Permission matrix" subtitle="Defined in config/gym.php. Trainers only see PT clients they coach; progress photos are limited to the member, their trainer and the gym admin." :padding="false">
        <div class="overflow-x-auto"><table class="table">
            <thead><tr><th>Permission</th>@foreach(config('gym.staff_roles') as $r)<th class="text-center">{{ config("gym.roles.$r") }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($matrix as $perm => $roles)
                <tr><td class="font-mono text-xs">{{ $perm }}</td>@foreach(config('gym.staff_roles') as $r)<td class="text-center">{!! in_array($r, $roles) ? '<span class="text-emerald-600">●</span>' : '<span class="text-slate-300">○</span>' !!}</td>@endforeach</tr>
            @endforeach
            </tbody>
        </table></div>
    </x-card>

    <x-modal wire:model="showForm" :title="$editingId ? 'Edit staff' : 'Invite staff'">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Name" name="name"><input wire:model="name" class="input"></x-field>
            <x-field label="Email" name="email"><input wire:model="email" class="input"></x-field>
            <x-field label="Phone" name="phone"><input wire:model="phone" class="input"></x-field>
            <x-field label="Role" name="role"><select wire:model="role" class="input">@foreach(config('gym.staff_roles') as $r)<option value="{{ $r }}">{{ config("gym.roles.$r") }}</option>@endforeach</select></x-field>
            <x-field label="Branch" name="branch_id"><select wire:model="branch_id" class="input"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></x-field>
            <label class="mt-7 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded text-brand"> Active</label>
            @unless($editingId)<p class="text-xs text-slate-500 sm:col-span-2">They'll receive an email to set their password. Trainers can then be completed under Trainers.</p>@endunless
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save</button></x-slot:footer>
    </x-modal>
</div>
