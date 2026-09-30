<div>
    <x-page-header title="My progress" subtitle="Tracked by your personal trainer."/>
    <x-tabs :tabs="['body' => 'Body measurements', 'photos' => 'Progress photos', 'fitness' => 'Fitness tests', 'goals' => 'Goals']" :active="$tab"/>

    @if($tab === 'body') @include('pt.partials.body-progress') @endif

    @if($tab === 'photos')
        <div class="mb-4 flex items-center gap-2 text-sm text-slate-500"><x-icon name="lock" class="size-4"/> Only you, your trainer and the gym admin can see these photos.</div>
        @if($dates->isEmpty())
            <div class="card"><x-empty icon="camera" title="No progress photos yet" text="Your trainer uploads photos at each assessment."/></div>
        @else
            @include('pt.partials.before-after')
        @endif
    @endif

    @if($tab === 'fitness')
        @if($fitness->isEmpty())
            <div class="card"><x-empty icon="target" title="No fitness assessments yet"/></div>
        @else
            <x-card :padding="false">
                <div class="overflow-x-auto"><table class="table">
                    <thead><tr><th>Test</th>@foreach($fitness as $f)<th class="text-right">{{ $f->assessed_on->format('d M y') }}</th>@endforeach</tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach(config('gym.fitness_ratings') as $k => $l)
                        <tr><td>{{ $l }} <span class="text-xs text-slate-400">/10</span></td>@foreach($fitness as $f)<td class="text-right">{{ $f->$k ?? '—' }}</td>@endforeach</tr>
                    @endforeach
                    @foreach(config('gym.fitness_tests') as $k => $t)
                        @continue($fitness->every(fn ($f) => $f->result($k) === null))
                        <tr><td class="font-medium">{{ $t['label'] }} <span class="text-xs text-slate-400">{{ $t['unit'] }}</span></td>@foreach($fitness as $f)<td class="text-right">{{ $f->result($k) !== null ? (float) $f->result($k) : '—' }}</td>@endforeach</tr>
                    @endforeach
                    </tbody>
                </table></div>
            </x-card>
        @endif
    @endif

    @if($tab === 'goals')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($goals as $goal) @include('pt.partials.goal-card', ['goal' => $goal])
            @empty <div class="card md:col-span-3"><x-empty icon="target" title="No goals yet"/></div> @endforelse
        </div>
    @endif
</div>
