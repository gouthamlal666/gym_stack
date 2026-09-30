<?php

namespace App\Livewire\Pt\Client;

use App\Models\NutritionPlan;
use Illuminate\Support\Facades\DB;

class Nutrition extends ClientTab
{
    public bool $editing = false;
    public ?int $planId = null;
    public $viewPlanId = '';

    public string $title = '';
    public int $daily_calories = 2000;
    public int $protein_g = 150;
    public int $carbs_g = 200;
    public int $fat_g = 60;
    public $water_liters = 3;
    public string $start_date = '';
    public ?string $notes = '';
    public array $meals = [];

    public function edit(?int $id = null): void
    {
        $this->authorizeCoach();
        $this->resetErrorBag();
        $plan = $id ? NutritionPlan::with('meals')->where('member_id', $this->member->id)->findOrFail($id) : null;
        $this->planId = $plan?->id;
        $this->title = $plan?->title ?? 'Nutrition plan';
        foreach (['daily_calories', 'protein_g', 'carbs_g', 'fat_g', 'water_liters'] as $f) {
            if ($plan) {
                $this->$f = $plan->$f;
            }
        }
        $this->start_date = ($plan?->start_date ?? today())->toDateString();
        $this->notes = $plan?->notes ?? '';
        $this->meals = $plan
            ? $plan->meals->map(fn ($m) => $m->only('meal_type', 'time', 'items', 'calories', 'protein_g', 'carbs_g', 'fat_g'))->all()
            : collect(config('gym.meal_types'))->keys()->map(fn ($t) => ['meal_type' => $t, 'time' => '', 'items' => '', 'calories' => '', 'protein_g' => '', 'carbs_g' => '', 'fat_g' => ''])->all();
        $this->editing = true;
    }

    /** Suggest macro targets from latest weight: protein 2 g/kg, fat 0.8 g/kg, carbs fill the rest. */
    public function suggestMacros(): void
    {
        $weight = $this->member->bodyAssessments()->latest('assessed_on')->value('weight');
        if (! $weight) {
            $this->toast('Record a body assessment first.', 'error');

            return;
        }
        $this->protein_g = (int) round($weight * 2);
        $this->fat_g = (int) round($weight * 0.8);
        $this->carbs_g = max(0, (int) round(($this->daily_calories - $this->protein_g * 4 - $this->fat_g * 9) / 4));
        $this->water_liters = round($weight * 0.035, 1);
    }

    public function addMeal(): void
    {
        $this->meals[] = ['meal_type' => 'lunch', 'time' => '', 'items' => '', 'calories' => '', 'protein_g' => '', 'carbs_g' => '', 'fat_g' => ''];
    }

    public function removeMeal(int $i): void
    {
        unset($this->meals[$i]);
        $this->meals = array_values($this->meals);
    }

    public function save(): void
    {
        $this->authorizeCoach();
        $this->validate([
            'title' => 'required|string|max:120', 'daily_calories' => 'required|integer|between:800,8000',
            'protein_g' => 'required|integer|between:0,600', 'carbs_g' => 'required|integer|between:0,1200', 'fat_g' => 'required|integer|between:0,400',
            'water_liters' => 'required|numeric|between:0,10', 'start_date' => 'required|date',
            'meals.*.meal_type' => 'required|in:'.implode(',', array_keys(config('gym.meal_types'))),
            'meals.*.items' => 'required|string|max:1000',
            'meals.*.calories' => 'nullable|integer|min:0', 'meals.*.protein_g' => 'nullable|integer|min:0',
            'meals.*.carbs_g' => 'nullable|integer|min:0', 'meals.*.fat_g' => 'nullable|integer|min:0',
        ], [], ['meals.*.items' => 'meal items']);

        DB::transaction(function () {
            $plan = $this->planId ? NutritionPlan::where('member_id', $this->member->id)->findOrFail($this->planId) : new NutritionPlan(['member_id' => $this->member->id]);
            $plan->fill([
                'trainer_id' => $plan->trainer_id ?? $this->trainerId(), 'title' => $this->title, 'daily_calories' => $this->daily_calories,
                'protein_g' => $this->protein_g, 'carbs_g' => $this->carbs_g, 'fat_g' => $this->fat_g, 'water_liters' => $this->water_liters,
                'start_date' => $this->start_date, 'notes' => $this->notes, 'is_active' => true,
            ])->save();
            NutritionPlan::where('member_id', $this->member->id)->where('id', '!=', $plan->id)->update(['is_active' => false]);
            $plan->meals()->delete();
            foreach (array_values($this->meals) as $i => $m) {
                $plan->meals()->create(collect($m)->map(fn ($v) => $v === '' ? null : $v)->all() + ['sort' => $i]);
            }
            $this->viewPlanId = $plan->id;
        });
        $this->editing = false;
        $this->toast('Nutrition plan saved.');
    }

    public function render()
    {
        $plans = NutritionPlan::where('member_id', $this->member->id)->latest('start_date')->latest('id')->get();
        $plan = $plans->firstWhere('id', (int) $this->viewPlanId) ?? $plans->firstWhere('is_active', true) ?? $plans->first();
        $plan?->load('meals', 'trainer.user');

        return view('livewire.pt.client.nutrition', ['plans' => $plans, 'plan' => $plan, 'canCoach' => $this->canCoach()]);
    }
}
