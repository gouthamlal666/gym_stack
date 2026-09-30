<div>
    <x-page-header title="Gyms" subtitle="Tenants, subscriptions and status.">
        <input wire:model.live.debounce.300ms="search" class="input w-64" placeholder="Search gyms">
        <button wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4"/> New gym</button>
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Gym</th><th>Admin</th><th>Branches</th><th>Members</th><th>Plan</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($gyms as $g)
                <tr>
                    <td><div class="font-medium">{{ $g->name }}</div><div class="text-xs text-slate-500">Since {{ $g->created_at->format('d M Y') }}</div></td>
                    <td class="text-sm">{{ $g->users->first()?->email }}</td>
                    <td>{{ $g->branches_count }}</td>
                    <td>{{ $g->members_count }}</td>
                    <td>
                        <select wire:change="setPlan({{ $g->id }}, $event.target.value)" class="input w-auto py-1 text-xs">
                            @foreach(['trial', 'starter', 'pro', 'enterprise'] as $p)<option value="{{ $p }}" @selected($g->subscription_plan === $p)>{{ ucfirst($p) }}</option>@endforeach
                        </select>
                    </td>
                    <td><x-status :value="$g->status"/></td>
                    <td class="text-right"><button wire:click="toggle({{ $g->id }})" wire:confirm="Change status of {{ $g->name }}?" class="btn-secondary btn-sm">{{ $g->status === 'active' ? 'Suspend' : 'Activate' }}</button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="border-t border-slate-100 p-4">{{ $gyms->links() }}</div>
    </div>
    <x-modal wire:model="showForm" title="New gym">
        <div class="space-y-4">
            <x-field label="Gym name" name="gym_name"><input wire:model="gym_name" class="input"></x-field>
            <x-field label="Admin name" name="admin_name"><input wire:model="admin_name" class="input"></x-field>
            <x-field label="Admin email" name="admin_email"><input wire:model="admin_email" class="input"></x-field>
            <x-field label="Subscription" name="subscription_plan"><select wire:model="subscription_plan" class="input">@foreach(['trial', 'starter', 'pro', 'enterprise'] as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select></x-field>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Create & invite</button></x-slot:footer>
    </x-modal>
</div>
