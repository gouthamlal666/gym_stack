<div class="space-y-6">
    @if($canCoach)
        <div class="flex justify-end"><button wire:click="openForm" class="btn-primary"><x-icon name="plus" class="size-4"/> {{ $assessments->isEmpty() ? 'Initial assessment' : 'New assessment' }}</button></div>
    @endif

    @include('pt.partials.body-progress')

    @if($assessments->isNotEmpty())
        <x-card title="Assessment history" :padding="false">
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Date</th>@foreach(config('gym.body_metrics') as $m)<th class="text-right">{{ $m['label'] }}</th>@endforeach<th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($assessments->reverse() as $x)
                    <tr>
                        <td class="whitespace-nowrap font-medium">{{ $x->assessed_on->format('d M Y') }} @if($x->is_initial)<x-badge color="brand">Initial</x-badge>@endif</td>
                        @foreach(array_keys(config('gym.body_metrics')) as $k)<td class="text-right">{{ $x->$k ?? '—' }}</td>@endforeach
                        <td class="whitespace-nowrap text-right">
                            @if($canCoach)
                                <button wire:click="openForm({{ $x->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4"/></button>
                                <button wire:click="delete({{ $x->id }})" wire:confirm="Delete this assessment?" class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4"/></button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </x-card>
    @endif

    <x-modal name="assessment" :title="$editingId ? 'Edit body assessment' : 'Body assessment'" max-width="max-w-3xl">
        <div class="grid gap-4 sm:grid-cols-4">
            <x-field label="Assessment date" name="assessed_on" class="sm:col-span-2"><input type="date" wire:model="assessed_on" class="input"></x-field>
            <x-field label="Next assessment" name="next_assessment_on" class="sm:col-span-2"><input type="date" wire:model="next_assessment_on" class="input"></x-field>
            <x-field label="Height (cm)" name="height"><input type="number" step="0.1" wire:model="height" class="input"></x-field>
            @foreach(config('gym.body_metrics') as $k => $m)
                @continue($k === 'bmi')
                <x-field :label="$m['label'].' ('.$m['unit'].')'" :name="'metrics.'.$k"><input type="number" step="0.1" wire:model="metrics.{{ $k }}" class="input"></x-field>
            @endforeach
            <div class="flex items-end text-xs text-slate-500">BMI is calculated from weight & height.</div>
            <x-field label="Notes" name="notes" class="sm:col-span-4"><textarea wire:model="notes" rows="2" class="input"></textarea></x-field>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save assessment</button></x-slot:footer>
    </x-modal>
</div>
