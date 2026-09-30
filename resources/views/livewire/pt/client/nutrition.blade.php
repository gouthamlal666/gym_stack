<div>
    @if($editing)
        <div class="space-y-4">
            <x-card :title="$planId ? 'Edit nutrition plan' : 'New nutrition plan'">
                <div class="grid gap-4 sm:grid-cols-6">
                    <x-field label="Title" name="title" class="sm:col-span-3"><input wire:model="title" class="input"></x-field>
                    <x-field label="Start date" name="start_date" class="sm:col-span-2"><input type="date" wire:model="start_date" class="input"></x-field>
                    <div class="flex items-end"><button wire:click="suggestMacros" class="btn-secondary w-full" title="From latest weight">✨ Suggest</button></div>
                    <x-field label="Daily calories" name="daily_calories"><input type="number" wire:model="daily_calories" class="input"></x-field>
                    <x-field label="Protein (g)" name="protein_g"><input type="number" wire:model="protein_g" class="input"></x-field>
                    <x-field label="Carbs (g)" name="carbs_g"><input type="number" wire:model="carbs_g" class="input"></x-field>
                    <x-field label="Fat (g)" name="fat_g"><input type="number" wire:model="fat_g" class="input"></x-field>
                    <x-field label="Water (L)" name="water_liters"><input type="number" step="0.1" wire:model="water_liters" class="input"></x-field>
                    <div class="flex items-end text-xs text-slate-500">Macros = {{ $protein_g * 4 + $carbs_g * 4 + $fat_g * 9 }} kcal</div>
                    <x-field label="Guidelines / notes" name="notes" class="sm:col-span-6"><textarea wire:model="notes" rows="2" class="input" placeholder="Supplements, cheat meal rules, hydration…"></textarea></x-field>
                </div>
            </x-card>
            <x-card title="Meals">
                <x-slot:actions><button wire:click="addMeal" class="btn-secondary btn-sm"><x-icon name="plus" class="size-4"/> Meal</button></x-slot:actions>
                <div class="space-y-3">
                    @foreach($meals as $i => $m)
                        <div class="grid gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-12" wire:key="meal-{{ $i }}">
                            <select wire:model="meals.{{ $i }}.meal_type" class="input py-1.5 sm:col-span-2">@foreach(config('gym.meal_types') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                            <input wire:model="meals.{{ $i }}.time" class="input py-1.5 sm:col-span-1" placeholder="07:30">
                            <div class="sm:col-span-5"><textarea wire:model="meals.{{ $i }}.items" rows="2" class="input py-1.5" placeholder="Oats 60g, whey 1 scoop, banana"></textarea>
                                @error("meals.$i.items")<p class="error">{{ $message }}</p>@enderror</div>
                            <input type="number" wire:model="meals.{{ $i }}.calories" class="input py-1.5 sm:col-span-1" placeholder="kcal">
                            <input type="number" wire:model="meals.{{ $i }}.protein_g" class="input py-1.5 sm:col-span-1" placeholder="P">
                            <input type="number" wire:model="meals.{{ $i }}.carbs_g" class="input py-1.5 sm:col-span-1" placeholder="C">
                            <div class="flex gap-1 sm:col-span-1"><input type="number" wire:model="meals.{{ $i }}.fat_g" class="input py-1.5" placeholder="F">
                                <button wire:click="removeMeal({{ $i }})" class="btn-ghost p-1 text-rose-600"><x-icon name="x" class="size-4"/></button></div>
                        </div>
                    @endforeach
                </div>
            </x-card>
            <div class="flex justify-end gap-2"><button wire:click="$set('editing', false)" class="btn-secondary">Cancel</button><button wire:click="save" class="btn-primary">Save plan</button></div>
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @if($plans->count() > 1)
                <select wire:model.live="viewPlanId" class="input w-auto">@foreach($plans as $p)<option value="{{ $p->id }}" @selected($plan?->id === $p->id)>{{ $p->title }} · {{ $p->start_date->format('d M Y') }}{{ $p->is_active ? ' (active)' : '' }}</option>@endforeach</select>
            @endif
            @if($canCoach)
                <div class="ml-auto flex gap-2">
                    @if($plan)<button wire:click="edit({{ $plan->id }})" class="btn-secondary"><x-icon name="pencil" class="size-4"/> Update plan</button>@endif
                    <button wire:click="edit" class="btn-primary"><x-icon name="plus" class="size-4"/> New plan</button>
                </div>
            @endif
        </div>
        @if($plan)
            @include('pt.partials.nutrition-plan', ['plan' => $plan])
        @else
            <div class="card"><x-empty icon="leaf" title="No nutrition plan yet" text="Set daily calorie, macro and water targets and a meal-by-meal plan."/></div>
        @endif
    @endif
</div>
