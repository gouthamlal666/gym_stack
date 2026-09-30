<div>
    @if($editing)
        <div class="space-y-4">
            <x-card :title="$planId ? 'Edit workout plan' : 'New workout plan'">
                <div class="grid gap-4 sm:grid-cols-6">
                    <x-field label="Title" name="title" class="sm:col-span-2"><input wire:model="title" class="input"></x-field>
                    <x-field label="Goal" name="goal"><input wire:model="goal" class="input" list="goals"></x-field>
                    <datalist id="goals">@foreach(config('gym.goal_types') as $g)<option value="{{ $g }}">@endforeach</datalist>
                    <x-field label="Level" name="level"><select wire:model="level" class="input"><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option></select></x-field>
                    <x-field label="Weeks" name="duration_weeks"><input type="number" wire:model="duration_weeks" class="input"></x-field>
                    <x-field label="Start" name="start_date"><input type="date" wire:model="start_date" class="input"></x-field>
                    <x-field label="Coach notes" name="notes" class="sm:col-span-6"><textarea wire:model="notes" rows="2" class="input"></textarea></x-field>
                </div>
            </x-card>
            <datalist id="exercise-library">@foreach($library as $ex)<option value="{{ $ex->name }}">{{ $ex->muscle_group }}</option>@endforeach</datalist>

            @foreach(config('gym.weekdays') as $d => $dayName)
                <div class="card" wire:key="day-{{ $d }}">
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3">
                        <div class="w-24 font-semibold">{{ $dayName }}</div>
                        <input wire:model="days.{{ $d }}.focus" class="input max-w-xs py-1.5" placeholder="{{ $days[$d]['is_rest'] ? 'Rest / Mobility' : 'e.g. Chest + Cardio' }}">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="days.{{ $d }}.is_rest" class="rounded text-brand"> Rest day</label>
                        @unless($days[$d]['is_rest'])<button wire:click="addExercise({{ $d }})" class="btn-secondary btn-sm ml-auto"><x-icon name="plus" class="size-4"/> Exercise</button>@endunless
                    </div>
                    @unless($days[$d]['is_rest'])
                        <div class="space-y-3 p-4">
                            @forelse($days[$d]['exercises'] as $i => $ex)
                                <div class="rounded-lg border border-slate-200 p-3" wire:key="ex-{{ $d }}-{{ $i }}">
                                    <div class="grid gap-2 sm:grid-cols-12">
                                        <div class="sm:col-span-4"><input wire:model="days.{{ $d }}.exercises.{{ $i }}.name" list="exercise-library" class="input py-1.5" placeholder="Exercise">
                                            @error("days.$d.exercises.$i.name")<p class="error">{{ $message }}</p>@enderror</div>
                                        <input type="number" wire:model="days.{{ $d }}.exercises.{{ $i }}.sets" class="input py-1.5 sm:col-span-1" placeholder="Sets" title="Sets">
                                        <input wire:model="days.{{ $d }}.exercises.{{ $i }}.reps" class="input py-1.5 sm:col-span-1" placeholder="Reps" title="Reps">
                                        <input wire:model="days.{{ $d }}.exercises.{{ $i }}.weight" class="input py-1.5 sm:col-span-2" placeholder="Weight" title="Weight">
                                        <input type="number" wire:model="days.{{ $d }}.exercises.{{ $i }}.rest_seconds" class="input py-1.5 sm:col-span-1" placeholder="Rest s" title="Rest (sec)">
                                        <input wire:model="days.{{ $d }}.exercises.{{ $i }}.tempo" class="input py-1.5 sm:col-span-1" placeholder="Tempo" title="Tempo e.g. 3-1-1">
                                        <input type="number" wire:model="days.{{ $d }}.exercises.{{ $i }}.rpe" class="input py-1.5 sm:col-span-1" placeholder="RPE" title="RPE 1-10">
                                        <div class="flex items-center justify-end gap-1 sm:col-span-1">
                                            <button wire:click="moveExercise({{ $d }}, {{ $i }}, -1)" class="btn-ghost p-1" title="Up">↑</button>
                                            <button wire:click="moveExercise({{ $d }}, {{ $i }}, 1)" class="btn-ghost p-1" title="Down">↓</button>
                                            <button wire:click="removeExercise({{ $d }}, {{ $i }})" class="btn-ghost p-1 text-rose-600"><x-icon name="x" class="size-4"/></button>
                                        </div>
                                        <input wire:model="days.{{ $d }}.exercises.{{ $i }}.instructions" class="input py-1.5 sm:col-span-5" placeholder="Trainer instructions">
                                        <input wire:model="days.{{ $d }}.exercises.{{ $i }}.video_url" class="input py-1.5 sm:col-span-3" placeholder="Video URL">
                                        <input wire:model="days.{{ $d }}.exercises.{{ $i }}.notes" class="input py-1.5 sm:col-span-4" placeholder="Notes">
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400">No exercises yet.</p>
                            @endforelse
                        </div>
                    @endunless
                </div>
            @endforeach
            <div class="sticky bottom-4 flex justify-end gap-2 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
                <button wire:click="$set('editing', false)" class="btn-secondary">Cancel</button>
                <button wire:click="save" class="btn-primary">Save plan</button>
            </div>
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @if($plans->count() > 1)
                <select wire:model.live="viewPlanId" class="input w-auto">@foreach($plans as $p)<option value="{{ $p->id }}" @selected($plan?->id === $p->id)>{{ $p->title }} · {{ $p->start_date->format('M Y') }}{{ $p->is_active ? ' (active)' : '' }}</option>@endforeach</select>
            @endif
            @if($canCoach)
                <div class="ml-auto flex gap-2">
                    @if($plan)<button wire:click="edit({{ $plan->id }}, true)" class="btn-secondary">Duplicate as new</button><button wire:click="edit({{ $plan->id }})" class="btn-secondary"><x-icon name="pencil" class="size-4"/> Edit</button>@endif
                    <button wire:click="edit" class="btn-primary"><x-icon name="plus" class="size-4"/> New plan</button>
                </div>
            @endif
        </div>
        @if($plan)
            @include('pt.partials.workout-plan', ['plan' => $plan])
        @else
            <div class="card"><x-empty icon="dumbbell" title="No PT workout plan yet" text="Build a weekly program with sets, reps, weight, rest, tempo, RPE, instructions and videos."/></div>
        @endif
    @endif
</div>
