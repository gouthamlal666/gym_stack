@php use App\Support\Money; $t = $trainer; @endphp
<div class="space-y-6">
    <x-page-header :title="$t->name" :subtitle="implode(' · ', $t->specializations ?? [])" :back="route('trainers.index')">
        <input type="month" wire:model.live="month" class="input w-auto">
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Completed sessions" :value="$completed" :hint="$scheduled.' still scheduled'" icon="check" tone="green"/>
        <x-stat label="No-shows / cancelled" :value="$noShows.' / '.$cancelled" icon="x" tone="red"/>
        <x-stat label="PT revenue collected" :value="Money::format($ptRevenue)" icon="cash" tone="blue"/>
        <x-stat label="Commission ({{ (float) $t->commission_rate }}%)" :value="Money::format($commission)" icon="star" tone="amber" :hint="$avgRating ? 'Avg session rating '.$avgRating.'/5' : null"/>
    </div>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Performance trend" class="lg:col-span-2"><x-chart :config="$chart"/></x-card>
        <x-card title="Profile">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-slate-500">Contact</dt><dd>{{ $t->user->email }}<br>{{ $t->user->phone }}</dd></div>
                <div><dt class="text-slate-500">Certifications</dt><dd>{{ implode(', ', $t->certifications ?? []) ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Availability</dt><dd>
                    @foreach(config('gym.weekdays') as $d => $day)
                        @if($h = $t->worksOn($d))<div>{{ substr($day, 0, 3) }}: {{ $h['start'] }}–{{ $h['end'] }}</div>@endif
                    @endforeach
                </dd></div>
                @if($t->bio)<div><dt class="text-slate-500">Bio</dt><dd>{{ $t->bio }}</dd></div>@endif
            </dl>
        </x-card>
    </div>
    <x-card title="Assigned PT clients" :padding="false">
        <table class="table"><thead><tr><th>Client</th><th>Package</th><th>Sessions left</th><th>Expires</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($clients as $s)
                <tr><td><a href="{{ route('pt.clients.show', $s->member) }}" wire:navigate class="font-medium hover:text-brand">{{ $s->member->name }}</a></td><td>{{ $s->package->name }}</td>
                    <td>{{ $s->remainingSessions() }} / {{ $s->total_sessions }}</td><td>{{ $s->expiry_date->format('d M Y') }}</td></tr>
            @empty
                <tr><td colspan="4"><x-empty icon="star" title="No active PT clients"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </x-card>
</div>
