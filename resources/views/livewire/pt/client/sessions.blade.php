<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-4">
        <x-stat label="Completed" :value="$stats['completed'] ?? 0" icon="check" tone="green"/>
        <x-stat label="Scheduled" :value="$stats['scheduled'] ?? 0" icon="calendar" tone="blue"/>
        <x-stat label="No-shows" :value="$stats['no_show'] ?? 0" icon="x" tone="red"/>
        <x-stat label="Cancelled" :value="$stats['cancelled'] ?? 0" icon="clock" tone="amber"/>
    </div>

    <x-card title="PT packages" :padding="false">
        <x-slot:actions>@if($canBook)<button wire:click="openBooking" class="btn-primary btn-sm"><x-icon name="plus" class="size-4"/> Book session</button>@endif</x-slot:actions>
        <table class="table"><thead><tr><th>Package</th><th>Trainer</th><th>Validity</th><th>Used</th><th>Remaining</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($subscriptions as $s)
                <tr><td class="font-medium">{{ $s->package->name }}</td><td>{{ $s->trainer->name }}</td><td>{{ $s->start_date->format('d M') }} – {{ $s->expiry_date->format('d M Y') }}</td>
                    <td class="w-40"><x-progress :value="$s->progressPercent()" color="bg-amber-500"/><span class="text-xs text-slate-500">{{ $s->consumedSessions() }} / {{ $s->total_sessions }}</span></td>
                    <td class="font-semibold">{{ $s->remainingSessions() }}</td><td><x-status :value="$s->status"/></td></tr>
            @endforeach
            </tbody>
        </table>
    </x-card>

    <x-card title="Session history" :padding="false">
        <div class="overflow-x-auto"><table class="table">
            <thead><tr><th>Date</th><th>Focus</th><th>Trainer</th><th>Duration</th><th>Calories</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($sessions as $s)
                <tr>
                    <td class="whitespace-nowrap">{{ $s->scheduled_at->format('D d M Y, H:i') }}</td>
                    <td>{{ $s->focus ?: '—' }}</td>
                    <td>{{ $s->trainer->name }}</td>
                    <td>{{ $s->actualMinutes() ?? $s->duration_minutes }} min</td>
                    <td>{{ $s->calories ? $s->calories.' kcal' : '—' }}</td>
                    <td><x-status :value="$s->status"/></td>
                    <td class="text-right">@can('pt.schedule')<a href="{{ route('pt.sessions.show', $s) }}" wire:navigate class="btn-secondary btn-sm">Open</a>@endcan</td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="calendar" title="No sessions yet"/></td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="border-t border-slate-100 p-4">{{ $sessions->links() }}</div>
    </x-card>

    <x-modal name="book" title="Book PT session">
        @include('pt.partials.booking-form', ['subscriptions' => $subscriptions])
        <x-slot:footer><button wire:click="book" class="btn-primary">Book</button></x-slot:footer>
    </x-modal>
</div>
