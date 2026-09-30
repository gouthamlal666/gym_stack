<div class="space-y-6">
    @if($canCoach)<div class="flex justify-end"><button wire:click="openForm" class="btn-primary"><x-icon name="plus" class="size-4"/> New fitness assessment</button></div>@endif

    @if($assessments->isEmpty())
        <div class="card"><x-empty icon="target" title="No fitness assessments yet" text="Record strength, flexibility, mobility, endurance, cardio, balance, posture and functional movement plus standard fitness tests."/></div>
    @else
        <div class="grid gap-6 lg:grid-cols-5">
            <x-card title="Fitness profile" :subtitle="'Latest: '.$latest->assessed_on->format('d M Y').' · overall '.$latest->overallScore().'/10'" class="lg:col-span-2">
                <x-chart :config="$radar" height="h-72" wire:key="radar-{{ $assessments->count() }}-{{ $latest->updated_at->timestamp }}"/>
            </x-card>
            <x-card title="Ratings (1–10)" class="lg:col-span-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach(config('gym.fitness_ratings') as $k => $label)
                        <div>
                            <div class="flex justify-between text-sm"><span>{{ $label }}</span><span class="font-semibold">{{ $latest->$k ?? '—' }}</span></div>
                            <x-progress :value="($latest->$k ?? 0) * 10" class="mt-1" :color="($latest->$k ?? 0) >= 7 ? 'bg-emerald-500' : (($latest->$k ?? 0) >= 4 ? 'bg-amber-500' : 'bg-rose-500')"/>
                        </div>
                    @endforeach
                </div>
                @if($latest->notes)<p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ $latest->notes }}</p>@endif
            </x-card>
        </div>

        <x-card title="Fitness test history" :padding="false">
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Test</th>@foreach($assessments as $a)<th class="text-right">{{ $a->assessed_on->format('d M y') }}</th>@endforeach<th class="text-right">Change</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach(config('gym.fitness_tests') as $k => $t)
                    @php $vals = $assessments->map(fn ($a) => $a->result($k)); $f = $vals->first(fn ($v) => $v !== null); $l = $vals->last(fn ($v) => $v !== null); @endphp
                    @continue($vals->filter(fn ($v) => $v !== null)->isEmpty())
                    <tr>
                        <td class="font-medium">{{ $t['label'] }} <span class="text-xs text-slate-400">{{ $t['unit'] }}</span></td>
                        @foreach($vals as $v)<td class="text-right">{{ $v !== null ? (float) $v : '—' }}</td>@endforeach
                        <td class="text-right">@if($f !== null && $l !== null && $vals->filter(fn ($v) => $v !== null)->count() > 1)<x-delta :value="$l - $f" :good-when="$t['higher_is_better'] ? 'up' : 'down'"/>@else — @endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($canCoach)
                <div class="flex flex-wrap gap-2 border-t border-slate-100 p-4">
                    @foreach($assessments->reverse() as $a)<button wire:click="openForm({{ $a->id }})" class="btn-secondary btn-sm">Edit {{ $a->assessed_on->format('d M Y') }}</button>@endforeach
                </div>
            @endif
        </x-card>
    @endif

    <x-modal name="fitness" title="Fitness assessment" max-width="max-w-3xl">
        <div class="space-y-5">
            <x-field label="Date" name="assessed_on" class="max-w-xs"><input type="date" wire:model="assessed_on" class="input"></x-field>
            <div>
                <div class="label">Ratings (1 = poor · 10 = excellent)</div>
                <div class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                    @foreach(config('gym.fitness_ratings') as $k => $label)
                        <div x-data>
                            <div class="flex justify-between text-sm"><span>{{ $label }}</span><span class="font-semibold" x-text="$wire.ratings.{{ $k }}"></span></div>
                            <input type="range" min="1" max="10" wire:model="ratings.{{ $k }}" x-on:input="$wire.ratings.{{ $k }} = $event.target.value" class="w-full accent-[var(--brand)]">
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="label">Fitness tests</div>
                <div class="grid gap-3 sm:grid-cols-4">
                    @foreach(config('gym.fitness_tests') as $k => $t)
                        <x-field :label="$t['label'].' ('.$t['unit'].')'" :name="'tests.'.$k"><input type="number" step="0.01" wire:model="tests.{{ $k }}" class="input"></x-field>
                    @endforeach
                </div>
            </div>
            <x-field label="Notes" name="notes"><textarea wire:model="notes" rows="2" class="input"></textarea></x-field>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save</button></x-slot:footer>
    </x-modal>
</div>
