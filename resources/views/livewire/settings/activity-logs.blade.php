<div>
    <x-page-header title="Activity log" subtitle="Audit trail of sign-ins and every change made in your gym."/>
    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <input wire:model.live.debounce.300ms="search" class="input max-w-xs" placeholder="Search…">
            <select wire:model.live="user" class="input w-auto"><option value="">Everyone</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
            <select wire:model.live="action" class="input w-auto"><option value="">All actions</option>@foreach($actions as $a)<option>{{ $a }}</option>@endforeach</select>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse($logs as $log)
                <li class="flex items-start gap-3 px-5 py-3" x-data="{ open: false }">
                    <x-avatar :name="$log->user?->name ?? 'System'" size="size-8"/>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm"><b>{{ $log->user?->name ?? 'System' }}</b> <span class="text-slate-600">{{ $log->description }}</span></div>
                        <div class="text-xs text-slate-400">{{ $log->created_at->format('d M Y H:i:s') }} · {{ $log->ip_address }} · <x-badge>{{ $log->action }}</x-badge>
                            @if($log->properties)<button @click="open = !open" class="ml-1 text-brand">details</button>@endif</div>
                        @if($log->properties)<pre x-show="open" x-cloak class="mt-2 overflow-x-auto rounded bg-slate-50 p-2 text-xs">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@endif
                    </div>
                </li>
            @empty
                <x-empty icon="clock" title="No activity"/>
            @endforelse
        </ul>
        <div class="border-t border-slate-100 p-4">{{ $logs->links() }}</div>
    </div>
</div>
