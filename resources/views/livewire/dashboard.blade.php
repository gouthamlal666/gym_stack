@php use App\Support\Money; @endphp
<div class="space-y-6">
    <x-page-header :title="'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening')).', '.explode(' ', auth()->user()->name)[0]" :subtitle="now()->format('l, d F Y')">
        @can('members.manage')<a href="{{ route('members.create') }}" wire:navigate class="btn-primary"><x-icon name="plus" class="size-4"/> New member</a>@endcan
        @can('attendance.manage')<a href="{{ route('attendance.index') }}" wire:navigate class="btn-secondary"><x-icon name="check" class="size-4"/> Check-in</a>@endcan
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Active members" :value="$activeMembers" :hint="$totalMembers.' total'" icon="users"/>
        <x-stat label="PT clients" :value="$ptClients" hint="Personal training customers" icon="star" tone="amber"/>
        <x-stat label="Check-ins today" :value="$checkinsToday" icon="check" tone="green"/>
        @can('billing.view')
            <x-stat label="Revenue this month" :value="Money::format($revenueMonth)" :hint="Money::format($outstanding).' outstanding · '.$overdueCount.' overdue'" icon="cash" tone="blue"/>
        @endcan
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        @can('finance.view')
            <x-card title="Revenue vs expenses" subtitle="Last 6 months" class="xl:col-span-2">
                <x-chart :config="$revenueChart" height="h-72"/>
            </x-card>
        @endcan

        <x-card title="Today's PT sessions" :padding="false" :class="auth()->user()->can('finance.view') ? '' : 'xl:col-span-3'">
            <x-slot:actions><a href="{{ route('pt.schedule') }}" wire:navigate class="text-sm text-brand hover:underline">Schedule →</a></x-slot:actions>
            <ul class="divide-y divide-slate-100">
                @forelse($todaySessions as $s)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <div class="w-14 text-sm font-semibold text-slate-900">{{ $s->scheduled_at->format('H:i') }}</div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('pt.sessions.show', $s) }}" wire:navigate class="block truncate text-sm font-medium hover:text-brand">{{ $s->member->name }}</a>
                            <div class="truncate text-xs text-slate-500">{{ $s->focus ?: 'Session' }} · {{ $s->trainer->name }}</div>
                        </div>
                        <x-status :value="$s->status"/>
                    </li>
                @empty
                    <x-empty icon="calendar" title="No PT sessions today"/>
                @endforelse
            </ul>
        </x-card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Expiring within 7 days" :padding="false">
            <x-slot:actions><a href="{{ route('memberships.index', ['filter' => 'expiring']) }}" wire:navigate class="text-sm text-brand hover:underline">View all →</a></x-slot:actions>
            <ul class="divide-y divide-slate-100">
                @forelse($expiring as $m)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <x-avatar :name="$m->member->name" size="size-8"/>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('members.show', $m->member) }}" wire:navigate class="block truncate text-sm font-medium hover:text-brand">{{ $m->member->name }}</a>
                            <div class="text-xs text-slate-500">{{ $m->plan->name }} · ends {{ $m->end_date->format('d M') }}</div>
                        </div>
                        <x-badge :color="$m->daysLeft() <= 2 ? 'red' : 'amber'">{{ $m->daysLeft() }}d left</x-badge>
                    </li>
                @empty
                    <x-empty icon="card" title="No memberships expiring soon"/>
                @endforelse
            </ul>
        </x-card>

        @can('billing.view')
            <x-card title="Recent payments" :padding="false">
                <x-slot:actions><a href="{{ route('payments.index') }}" wire:navigate class="text-sm text-brand hover:underline">View all →</a></x-slot:actions>
                <ul class="divide-y divide-slate-100">
                    @forelse($recentPayments as $p)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">{{ $p->member->name }}</div>
                                <div class="text-xs text-slate-500">{{ $p->receipt_number }} · {{ $p->methodLabel() }} · {{ $p->paid_at->diffForHumans() }}</div>
                            </div>
                            <div @class(['text-sm font-semibold', 'text-emerald-600' => $p->type === 'payment', 'text-rose-600' => $p->type === 'refund'])>
                                {{ $p->type === 'refund' ? '−' : '' }}{{ Money::format($p->amount) }}
                            </div>
                        </li>
                    @empty
                        <x-empty icon="cash" title="No payments yet"/>
                    @endforelse
                </ul>
            </x-card>
        @endcan
    </div>
</div>
