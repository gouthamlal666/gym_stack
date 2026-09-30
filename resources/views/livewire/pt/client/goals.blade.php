<div class="space-y-4">
    @if($canCoach)<div class="flex justify-end"><button wire:click="openForm" class="btn-primary"><x-icon name="plus" class="size-4"/> New goal</button></div>@endif
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($goals as $goal)
            <div wire:key="goal-{{ $goal->id }}">
                @include('pt.partials.goal-card', ['goal' => $goal])
                @if($canCoach)
                    <div class="-mt-2 flex gap-2 rounded-b-xl border border-t-0 border-slate-200 bg-slate-50 px-4 py-2">
                        <button wire:click="openForm({{ $goal->id }})" class="btn-ghost btn-sm">Edit</button>
                        @if($goal->status === 'active')
                            <button wire:click="setStatus({{ $goal->id }}, 'achieved')" class="btn-ghost btn-sm text-emerald-700">Mark achieved 🎉</button>
                            <button wire:click="setStatus({{ $goal->id }}, 'abandoned')" class="btn-ghost btn-sm text-slate-500">Abandon</button>
                        @else
                            <button wire:click="setStatus({{ $goal->id }}, 'active')" class="btn-ghost btn-sm">Reactivate</button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="card md:col-span-3"><x-empty icon="target" title="No goals yet" text="Every PT customer should have a specific, measurable goal."/></div>
        @endforelse
    </div>

    <x-modal name="goal" :title="$editingId ? 'Edit goal' : 'New goal'">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Goal type" name="goal_type"><select wire:model="goal_type" class="input">@foreach(config('gym.goal_types') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
            <x-field label="Tracked measurement" name="metric"><select wire:model.live="metric" class="input">@foreach(config('gym.body_metrics') as $k => $m)<option value="{{ $k }}">{{ $m['label'] }}</option>@endforeach</select></x-field>
            <x-field label="Title" name="title" class="sm:col-span-2"><input wire:model="title" class="input" placeholder="e.g. Lose 8 kg"></x-field>
            <x-field :label="'Starting value ('.config('gym.body_metrics.'.$metric.'.unit').')'" name="start_value"><input type="number" step="0.1" wire:model="start_value" class="input"></x-field>
            <x-field :label="'Target value ('.config('gym.body_metrics.'.$metric.'.unit').')'" name="target_value"><input type="number" step="0.1" wire:model="target_value" class="input"></x-field>
            <x-field label="Start date" name="start_date"><input type="date" wire:model="start_date" class="input"></x-field>
            <x-field label="Target date" name="target_date"><input type="date" wire:model="target_date" class="input"></x-field>
            <p class="text-xs text-slate-500 sm:col-span-2">Current value and progress update automatically from each new body assessment.</p>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save goal</button></x-slot:footer>
    </x-modal>
</div>
