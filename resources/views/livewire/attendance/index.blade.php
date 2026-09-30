<div>
    <x-page-header title="Attendance" subtitle="Front-desk check-in and daily visit log."/>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Quick check-in">
            <div class="relative">
                <x-icon name="search" class="absolute top-2.5 left-3 size-4 text-slate-400"/>
                <input wire:model.live.debounce.250ms="search" class="input pl-9" placeholder="Name, phone or member ID" autofocus>
            </div>
            <ul class="mt-3 space-y-2">
                @foreach($results as $m)
                    <li class="flex items-center gap-3 rounded-lg border border-slate-200 p-2.5">
                        <x-avatar :name="$m->name" :src="$m->photoUrl()" size="size-9"/>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium">{{ $m->name }}</div>
                            <div class="text-xs {{ $m->currentMembership ? 'text-slate-500' : 'text-rose-600' }}">{{ $m->currentMembership ? $m->currentMembership->plan->name.' · '.$m->currentMembership->daysLeft().'d left' : 'No active membership' }}</div>
                        </div>
                        <button wire:click="checkIn({{ $m->id }})" class="btn-primary btn-sm">Check in</button>
                    </li>
                @endforeach
            </ul>
            @if($date === today()->toDateString())
                <div class="mt-6 text-center"><div class="text-4xl font-semibold">{{ $records->count() }}</div><div class="text-sm text-slate-500">visits today · {{ $records->whereNull('check_out_at')->count() }} in the gym now</div></div>
            @endif
        </x-card>

        <x-card title="Visit log" :padding="false" class="lg:col-span-2">
            <x-slot:actions><input type="date" wire:model.live="date" class="input w-auto py-1.5"></x-slot:actions>
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex h-20 items-end gap-1">
                    @php $max = max(1, $hourly->max() ?? 1); @endphp
                    @foreach(range(5, 22) as $h)
                        <div class="flex flex-1 flex-col items-center gap-1" title="{{ $h }}:00 — {{ $hourly[$h] ?? 0 }}">
                            <div class="w-full rounded-t bg-brand/70" style="height: {{ (($hourly[$h] ?? 0) / $max) * 64 }}px"></div>
                            <span class="text-[10px] text-slate-400">{{ $h }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Member</th><th>In</th><th>Out</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($records as $r)
                    <tr>
                        <td><a href="{{ route('members.show', $r->member) }}" wire:navigate class="font-medium hover:text-brand">{{ $r->member->name }}</a></td>
                        <td>{{ $r->check_in_at->format('H:i') }}</td>
                        <td>{{ $r->check_out_at?->format('H:i') ?? '—' }}</td>
                        <td class="text-right">@unless($r->check_out_at)<button wire:click="checkOut({{ $r->id }})" class="btn-secondary btn-sm">Check out</button>@endunless</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty icon="check" title="No visits on this day"/></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-card>
    </div>
</div>
