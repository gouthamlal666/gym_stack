<div>
    <x-page-header title="Members" subtitle="Everyone who trains at your gym.">
        @can('members.manage')<a href="{{ route('members.create') }}" wire:navigate class="btn-primary"><x-icon name="plus" class="size-4"/> Add member</a>@endcan
    </x-page-header>

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <div class="relative min-w-60 flex-1">
                <x-icon name="search" class="absolute top-2.5 left-3 size-4 text-slate-400"/>
                <input wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Search name, phone, email or member ID">
            </div>
            <select wire:model.live="type" class="input w-auto">
                <option value="">All training types</option>
                <option value="regular">Regular gym members</option>
                <option value="pt">Personal training customers</option>
            </select>
            <select wire:model.live="status" class="input w-auto">
                <option value="">Any membership</option>
                <option value="active">Active membership</option>
                <option value="no_plan">No active membership</option>
            </select>
            @if($branches->count() > 1)
                <select wire:model.live="branch" class="input w-auto">
                    <option value="">All branches</option>
                    @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Member</th><th>Member ID</th><th>Training</th><th>Membership</th><th>Trainer</th><th>Joined</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($members as $m)
                    <tr wire:key="m-{{ $m->id }}">
                        <td>
                            <a href="{{ route('members.show', $m) }}" wire:navigate class="flex items-center gap-3">
                                <x-avatar :name="$m->name" :src="$m->photoUrl()" size="size-9"/>
                                <div><div class="font-medium text-slate-900 hover:text-brand">{{ $m->name }}</div><div class="text-xs text-slate-500">{{ $m->phone }}</div></div>
                            </a>
                        </td>
                        <td class="font-mono text-xs">{{ $m->member_code }}</td>
                        <td>@if($m->isPt())<x-badge color="amber">⭐ Personal training</x-badge>@else<x-badge>Regular</x-badge>@endif</td>
                        <td>
                            @if($m->currentMembership)
                                <div class="text-sm">{{ $m->currentMembership->plan->name }}</div>
                                <div class="text-xs text-slate-500">until {{ $m->currentMembership->end_date->format('d M Y') }}</div>
                            @else
                                <x-badge color="red">None</x-badge>
                            @endif
                        </td>
                        <td class="text-sm">{{ $m->trainer?->name ?? '—' }}</td>
                        <td class="text-sm text-slate-500">{{ $m->joined_on->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="users" title="No members found" text="Try a different search or add your first member."/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $members->links() }}</div>
    </div>
</div>
