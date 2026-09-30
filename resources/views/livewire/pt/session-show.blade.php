@php $s = $session; $sub = $s->subscription; @endphp
<div class="mx-auto max-w-6xl">
    <x-page-header :title="($s->focus ?: 'PT session').' · '.$s->member->name" :subtitle="$s->scheduled_at->format('l, d M Y · h:i A').' · '.$s->duration_minutes.' min with '.$s->trainer->name" :back="route('pt.schedule', ['week' => $s->scheduled_at->copy()->startOfWeek()->toDateString()])">
        <x-status :value="$s->status" class="text-sm"/>
        @if($open)
            <button wire:click="$set('modal', 'reschedule')" class="btn-secondary">Reschedule</button>
            <button wire:click="$set('modal', 'cancel')" class="btn-secondary">Cancel</button>
            <button wire:click="noShow" wire:confirm="Mark as no-show? {{ config('gym.no_show_consumes_session') ? 'This consumes one session from the package.' : '' }}" class="btn-secondary text-rose-600">No-show</button>
            @if($canCoach && $s->status === 'scheduled')<button wire:click="start" class="btn-primary"><x-icon name="play" class="size-4"/> Start session</button>@endif
        @endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if($s->status === 'in_progress')
                <div class="flex items-center justify-between rounded-xl bg-violet-600 p-4 text-white" x-data="{ start: {{ $s->started_at->timestamp }}, now: Math.floor(Date.now() / 1000) }" x-init="setInterval(() => now = Math.floor(Date.now() / 1000), 1000)">
                    <div class="flex items-center gap-2"><span class="size-2.5 animate-pulse rounded-full bg-white"></span> Session in progress</div>
                    <div class="font-mono text-2xl" x-text="new Date((now - start) * 1000).toISOString().substring(11, 19)"></div>
                </div>
            @endif

            <x-card title="Exercises performed">
                <x-slot:actions>
                    @if($canCoach)
                        <button wire:click="loadFromPlan" class="btn-secondary btn-sm">Load from workout plan</button>
                        <button wire:click="addExercise" class="btn-secondary btn-sm"><x-icon name="plus" class="size-4"/> Exercise</button>
                    @endif
                </x-slot:actions>
                <datalist id="lib">@foreach($library as $n)<option value="{{ $n }}">@endforeach</datalist>
                <div class="space-y-2">
                    @if(count($exercises))
                        <div class="hidden grid-cols-12 gap-2 px-1 text-xs font-medium text-slate-500 xl:grid"><div class="col-span-4">Exercise</div><div>Sets</div><div>Reps</div><div class="col-span-2">Weight (kg)</div><div>Min</div><div class="col-span-3">Notes</div></div>
                    @endif
                    @forelse($exercises as $i => $e)
                        <div class="grid grid-cols-6 gap-2 rounded-lg border border-slate-200 p-2 xl:grid-cols-12 xl:border-0 xl:p-0" wire:key="se-{{ $i }}">
                            <div class="col-span-6 xl:col-span-4"><input wire:model="exercises.{{ $i }}.name" list="lib" class="input py-1.5" placeholder="Exercise" @disabled(! $canCoach)>@error("exercises.$i.name")<p class="error">{{ $message }}</p>@enderror</div>
                            <input type="number" wire:model="exercises.{{ $i }}.sets" class="input py-1.5" placeholder="Sets" @disabled(! $canCoach)>
                            <input wire:model="exercises.{{ $i }}.reps" class="input py-1.5" placeholder="Reps" @disabled(! $canCoach)>
                            <input type="number" step="0.5" wire:model="exercises.{{ $i }}.weight" class="input py-1.5 xl:col-span-2" placeholder="kg" @disabled(! $canCoach)>
                            <input type="number" wire:model="exercises.{{ $i }}.duration_minutes" class="input py-1.5" placeholder="min" @disabled(! $canCoach)>
                            <div class="col-span-2 flex gap-1 xl:col-span-3"><input wire:model="exercises.{{ $i }}.notes" class="input py-1.5" placeholder="Notes" @disabled(! $canCoach)>
                                @if($canCoach)<button wire:click="removeExercise({{ $i }})" class="btn-ghost p-1 text-rose-600"><x-icon name="x" class="size-4"/></button>@endif</div>
                        </div>
                    @empty
                        <x-empty icon="dumbbell" title="No exercises logged" text="Add exercises or load today's plan."/>
                    @endforelse
                </div>
            </x-card>

            <x-card title="Trainer notes & feedback">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="Focus" name="focus"><input wire:model="focus" class="input" @disabled(! $canCoach)></x-field>
                    <x-field label="Calories burned" name="calories"><input type="number" wire:model="calories" class="input" @disabled(! $canCoach)></x-field>
                    <x-field label="Effort rating (1–5)" name="rating"><select wire:model="rating" class="input" @disabled(! $canCoach)><option value="">—</option>@foreach(range(1, 5) as $r)<option value="{{ $r }}">{{ str_repeat('★', $r) }}</option>@endforeach</select></x-field>
                    <x-field label="Private trainer notes" name="trainer_notes" class="sm:col-span-3"><textarea wire:model="trainer_notes" rows="2" class="input" placeholder="Form cues, pain points, progressions…" @disabled(! $canCoach)></textarea></x-field>
                    <x-field label="Feedback for the client" name="trainer_feedback" class="sm:col-span-3" hint="Visible to the client in their portal."><textarea wire:model="trainer_feedback" rows="2" class="input" @disabled(! $canCoach)></textarea></x-field>
                </div>
                @if($canCoach)
                    <div class="mt-4 flex justify-end gap-2">
                        <button wire:click="saveLog" class="btn-secondary">Save log</button>
                        @if($open)<button wire:click="complete" class="btn-primary"><x-icon name="check" class="size-4"/> Complete session</button>@endif
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Package">
                <div class="font-medium">{{ $sub->package->name }}</div>
                <div class="mt-3 flex justify-between text-sm"><span>Completed {{ $sub->completedSessions() }}</span><span>Remaining <b>{{ $sub->remainingSessions() }}</b></span></div>
                <x-progress :value="$sub->progressPercent()" class="mt-1.5" color="bg-amber-500"/>
                <div class="mt-2 text-xs text-slate-500">Expires {{ $sub->expiry_date->format('d M Y') }}</div>
                <a href="{{ route('pt.clients.show', $s->member) }}" wire:navigate class="btn-secondary btn-sm mt-4 w-full">Client PT profile</a>
            </x-card>
            @if($s->started_at)
                <x-card title="Timing">
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Started</dt><dd>{{ $s->started_at->format('H:i') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Ended</dt><dd>{{ $s->ended_at?->format('H:i') ?? '—' }}</dd></div>
                        @if($s->actualMinutes())<div class="flex justify-between"><dt class="text-slate-500">Duration</dt><dd>{{ $s->actualMinutes() }} min</dd></div>@endif
                    </dl>
                </x-card>
            @endif
            @if($s->cancel_reason)<x-card title="Cancellation"><p class="text-sm">{{ $s->cancel_reason }}</p></x-card>@endif
            @if($previous)
                <x-card :title="'Last session · '.$previous->scheduled_at->format('d M')" subtitle="For progressive overload">
                    <ul class="space-y-1.5 text-sm">
                        @foreach($previous->exercises as $e)
                            <li class="flex justify-between gap-2"><span class="truncate">{{ $e->name }}</span><span class="shrink-0 text-slate-500">{{ $e->sets }}×{{ $e->reps }} @if($e->weight)@ {{ (float) $e->weight }}kg @endif</span></li>
                        @endforeach
                    </ul>
                    @if($previous->trainer_notes)<p class="mt-3 text-xs text-slate-500 italic">{{ $previous->trainer_notes }}</p>@endif
                </x-card>
            @endif
        </div>
    </div>

    <x-modal name="reschedule" title="Reschedule session">
        <x-field label="New date & time" name="new_datetime"><input type="datetime-local" wire:model="new_datetime" class="input"></x-field>
        <x-slot:footer><button wire:click="reschedule" class="btn-primary">Reschedule</button></x-slot:footer>
    </x-modal>
    <x-modal name="cancel" title="Cancel session">
        <x-field label="Reason" name="cancel_reason"><input wire:model="cancel_reason" class="input"></x-field>
        <x-slot:footer><button wire:click="cancel" class="btn-danger">Cancel session</button></x-slot:footer>
    </x-modal>
</div>
