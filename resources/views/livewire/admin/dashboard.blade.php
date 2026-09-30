<div class="space-y-6">
    <x-page-header title="Platform overview" subtitle="All tenants on GymStack."/>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Gyms" :value="$gyms" :hint="$activeGyms.' active'" icon="building"/>
        <x-stat label="Members (all gyms)" :value="number_format($members)" icon="users" tone="green"/>
        <x-stat label="Staff accounts" :value="$staff" icon="shield" tone="blue"/>
        <x-stat label="Payments processed this month" :value="number_format($gmv, 2)" icon="cash" tone="amber"/>
    </div>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Growth" class="lg:col-span-2"><x-chart :config="$chart"/></x-card>
        <x-card title="Newest gyms" :padding="false">
            <ul class="divide-y divide-slate-100">
                @foreach($recent as $g)
                    <li class="flex items-center justify-between px-5 py-3 text-sm"><span class="font-medium">{{ $g->name }}</span><x-status :value="$g->status"/></li>
                @endforeach
            </ul>
        </x-card>
    </div>
</div>
