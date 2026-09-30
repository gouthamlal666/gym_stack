<div>
    <x-page-header title="PT clients" :subtitle="auth()->user()->isTrainer() ? 'Personal training customers you coach.' : 'All personal training customers.'">
        <input wire:model.live.debounce.300ms="search" class="input w-56" placeholder="Search">
        @if($trainers->isNotEmpty())
            <select wire:model.live="trainer" class="input w-auto"><option value="">All trainers</option>@foreach($trainers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
        @endif
    </x-page-header>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($clients as $c)
            @php $sub = $c->activePtSubscription; $goal = $c->goals->first(); @endphp
            <a href="{{ route('pt.clients.show', $c) }}" wire:navigate class="card group block p-5 transition hover:border-brand/40 hover:shadow-md">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$c->name" :src="$c->photoUrl()" size="size-12"/>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-semibold group-hover:text-brand">{{ $c->name }}</div>
                        <div class="text-xs text-slate-500">{{ $sub ? 'with '.$sub->trainer->name : 'No active package' }}</div>
                    </div>
                    @if($goal)<x-badge color="amber">{{ $goal->typeLabel() }}</x-badge>@endif
                </div>
                @if($sub)
                    <div class="mt-4 flex justify-between text-sm"><span class="text-slate-500">{{ $sub->package->name }}</span><span class="font-medium">{{ $sub->consumedSessions() }}/{{ $sub->total_sessions }}</span></div>
                    <x-progress :value="$sub->progressPercent()" class="mt-1.5" color="bg-amber-500"/>
                    <div class="mt-1.5 flex justify-between text-xs text-slate-500"><span>{{ $sub->remainingSessions() }} remaining</span><span @class(['text-rose-600' => $sub->expiry_date->diffInDays(now()) < 7])>expires {{ $sub->expiry_date->format('d M') }}</span></div>
                @endif
                @if($goal)
                    <div class="mt-3 flex items-center gap-2 text-xs text-slate-600"><x-icon name="target" class="size-4 text-brand"/> {{ $goal->title }} · {{ $goal->progressPercent() }}%</div>
                @endif
            </a>
        @empty
            <div class="card md:col-span-3"><x-empty icon="star" title="No PT clients" text="Enroll a member in a PT package from their member profile."/></div>
        @endforelse
    </div>
</div>
