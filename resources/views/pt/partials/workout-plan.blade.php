{{-- Read-only PT workout plan. Expects $plan --}}
<div class="card p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">{{ $plan->title }}</div>
            <div class="mt-1 flex flex-wrap gap-2">
                <x-badge color="amber">Goal: {{ $plan->goal }}</x-badge>
                <x-badge color="brand">{{ ucfirst($plan->level) }}</x-badge>
                <x-badge>{{ $plan->duration_weeks }} weeks</x-badge>
                @if($plan->is_active)<x-badge color="green">Week {{ $plan->currentWeek() }} of {{ $plan->duration_weeks }}</x-badge>@else<x-badge>Archived</x-badge>@endif
            </div>
        </div>
        <div class="text-right text-sm text-slate-500">{{ $plan->start_date->format('d M Y') }} – {{ $plan->endDate()->format('d M Y') }}<br>by {{ $plan->trainer?->name }}</div>
    </div>
    @if($plan->notes)<p class="mt-3 text-sm text-slate-600">{{ $plan->notes }}</p>@endif
</div>
<div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3" x-data="{ open: {{ now()->dayOfWeekIso }} }">
    @foreach(config('gym.weekdays') as $d => $dayName)
        @php $day = $plan->days->firstWhere('day_of_week', $d); @endphp
        <div @class(['card overflow-hidden', 'ring-2 ring-brand' => $d === now()->dayOfWeekIso])>
            <div @class(['flex items-center justify-between px-4 py-3', 'bg-brand-50' => $d === now()->dayOfWeekIso, 'bg-slate-50' => $d !== now()->dayOfWeekIso])>
                <div><div class="text-xs font-semibold text-slate-500 uppercase">{{ $dayName }} @if($d === now()->dayOfWeekIso)· Today @endif</div>
                    <div class="font-semibold">{{ $day ? ($day->is_rest ? '🧘 '.($day->focus ?: 'Rest') : $day->focus) : 'Rest' }}</div></div>
                @if($day && ! $day->is_rest)<x-badge>{{ $day->exercises->count() }} ex.</x-badge>@endif
            </div>
            @if($day && ! $day->is_rest && $day->exercises->isNotEmpty())
                <ol class="divide-y divide-slate-100">
                    @foreach($day->exercises as $i => $e)
                        <li class="px-4 py-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="text-sm font-medium">{{ $i + 1 }}. {{ $e->name }}</div>
                                @if($e->video_url)<a href="{{ $e->video_url }}" target="_blank" rel="noopener" class="text-brand"><x-icon name="play" class="size-4"/></a>@endif
                            </div>
                            <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500">
                                @if($e->sets)<span>{{ $e->sets }} × {{ $e->reps }}</span>@endif
                                @if($e->weight)<span>@ {{ $e->weight }}</span>@endif
                                @if($e->rest_seconds)<span>rest {{ $e->rest_seconds }}s</span>@endif
                                @if($e->tempo)<span>tempo {{ $e->tempo }}</span>@endif
                                @if($e->rpe)<span>RPE {{ $e->rpe }}</span>@endif
                            </div>
                            @if($e->instructions)<div class="mt-1 text-xs text-slate-600">💬 {{ $e->instructions }}</div>@endif
                            @if($e->notes)<div class="text-xs text-slate-400 italic">{{ $e->notes }}</div>@endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    @endforeach
</div>
