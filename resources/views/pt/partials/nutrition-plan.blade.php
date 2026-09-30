{{-- Read-only nutrition plan. Expects $plan --}}
<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="$plan->title" :subtitle="'Since '.$plan->start_date->format('d M Y').($plan->trainer ? ' · by '.$plan->trainer->name : '')">
        <div class="text-center"><div class="text-4xl font-bold">{{ number_format($plan->daily_calories) }}</div><div class="text-sm text-slate-500">kcal / day</div></div>
        <x-chart height="h-44" class="mt-4" wire:key="macro-{{ $plan->id }}-{{ $plan->updated_at->timestamp }}" :config="['type' => 'doughnut', 'labels' => ['Protein', 'Carbs', 'Fat'], 'datasets' => [['data' => [$plan->protein_g * 4, $plan->carbs_g * 4, $plan->fat_g * 9], 'colors' => ['#4f46e5', '#10b981', '#f59e0b']]], 'options' => ['cutout' => '65%']]"/>
        <div class="mt-4 grid grid-cols-4 gap-2 text-center text-sm">
            <div><div class="font-semibold text-brand">{{ $plan->protein_g }}g</div><div class="text-xs text-slate-500">Protein</div></div>
            <div><div class="font-semibold text-emerald-600">{{ $plan->carbs_g }}g</div><div class="text-xs text-slate-500">Carbs</div></div>
            <div><div class="font-semibold text-amber-600">{{ $plan->fat_g }}g</div><div class="text-xs text-slate-500">Fat</div></div>
            <div><div class="font-semibold text-sky-600">{{ (float) $plan->water_liters }}L</div><div class="text-xs text-slate-500">Water</div></div>
        </div>
        @if($plan->notes)<p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ $plan->notes }}</p>@endif
    </x-card>
    <x-card title="Meal plan" class="lg:col-span-2" :padding="false">
        <ul class="divide-y divide-slate-100">
            @foreach($plan->meals as $m)
                <li class="flex gap-4 px-5 py-4">
                    <div class="w-28 shrink-0"><div class="text-sm font-semibold">{{ $m->label() }}</div><div class="text-xs text-slate-500">{{ $m->time }}</div></div>
                    <div class="min-w-0 flex-1 text-sm whitespace-pre-line text-slate-700">{{ $m->items }}</div>
                    @if($m->calories)<div class="shrink-0 text-right text-xs text-slate-500"><div class="font-semibold text-slate-900">{{ $m->calories }} kcal</div>P{{ $m->protein_g ?? 0 }} · C{{ $m->carbs_g ?? 0 }} · F{{ $m->fat_g ?? 0 }}</div>@endif
                </li>
            @endforeach
        </ul>
    </x-card>
</div>
