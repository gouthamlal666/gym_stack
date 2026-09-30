<?php

namespace App\Livewire\Pt\Client;

use App\Models\Exercise;
use App\Models\PtWorkoutPlan;
use Illuminate\Support\Facades\DB;

/** PT-specific workout programming (separate from general workout management). */
class Workout extends ClientTab
{
    public bool $editing = false;
    public ?int $planId = null;
    public $viewPlanId = '';

    public string $title = '';
    public string $goal = 'Fat Loss';
    public string $level = 'intermediate';
    public int $duration_weeks = 12;
    public string $start_date = '';
    public ?string $notes = '';
    public array $days = [];

    public function edit(?int $id = null, bool $duplicate = false): void
    {
        $this->authorizeCoach();
        $this->resetErrorBag();
        $plan = $id ? PtWorkoutPlan::with('days.exercises')->where('member_id', $this->member->id)->findOrFail($id) : null;

        $this->planId = $duplicate ? null : $plan?->id;
        $this->title = $plan ? $plan->title.($duplicate ? ' (copy)' : '') : 'PT Program';
        $this->goal = $plan?->goal ?? ($this->member->goals()->where('status', 'active')->first()?->typeLabel() ?? 'Fat Loss');
        $this->level = $plan?->level ?? 'intermediate';
        $this->duration_weeks = $plan?->duration_weeks ?? 12;
        $this->start_date = ($duplicate || ! $plan ? today() : $plan->start_date)->toDateString();
        $this->notes = $plan?->notes ?? '';

        $this->days = collect(config('gym.weekdays'))->mapWithKeys(function ($name, $d) use ($plan) {
            $day = $plan?->days->firstWhere('day_of_week', $d);

            return [$d => [
                'focus' => $day?->focus ?? '',
                'is_rest' => $day?->is_rest ?? ($d === 7),
                'exercises' => $day ? $day->exercises->map(fn ($e) => $e->only('name', 'sets', 'reps', 'weight', 'rest_seconds', 'tempo', 'rpe', 'instructions', 'video_url', 'notes'))->all() : [],
            ]];
        })->all();
        $this->editing = true;
    }

    public function addExercise(int $day): void
    {
        $this->days[$day]['exercises'][] = ['name' => '', 'sets' => 3, 'reps' => '10', 'weight' => '', 'rest_seconds' => 60, 'tempo' => '', 'rpe' => 7, 'instructions' => '', 'video_url' => '', 'notes' => ''];
    }

    public function removeExercise(int $day, int $i): void
    {
        unset($this->days[$day]['exercises'][$i]);
        $this->days[$day]['exercises'] = array_values($this->days[$day]['exercises']);
    }

    public function moveExercise(int $day, int $i, int $dir): void
    {
        $list = $this->days[$day]['exercises'];
        $j = $i + $dir;
        if (isset($list[$j])) {
            [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
            $this->days[$day]['exercises'] = $list;
        }
    }

    public function save(): void
    {
        $this->authorizeCoach();
        $this->validate([
            'title' => 'required|string|max:120', 'goal' => 'required|string|max:60',
            'level' => 'required|in:beginner,intermediate,advanced', 'duration_weeks' => 'required|integer|between:1,52',
            'start_date' => 'required|date', 'notes' => 'nullable|string|max:2000',
            'days.*.focus' => 'nullable|string|max:80',
            'days.*.exercises.*.name' => 'required|string|max:120',
            'days.*.exercises.*.sets' => 'nullable|integer|between:1,20',
            'days.*.exercises.*.rpe' => 'nullable|integer|between:1,10',
            'days.*.exercises.*.rest_seconds' => 'nullable|integer|between:0,900',
            'days.*.exercises.*.video_url' => 'nullable|url',
        ], [], ['days.*.exercises.*.name' => 'exercise name']);

        DB::transaction(function () {
            $plan = $this->planId ? PtWorkoutPlan::where('member_id', $this->member->id)->findOrFail($this->planId) : new PtWorkoutPlan(['member_id' => $this->member->id]);
            $plan->fill([
                'trainer_id' => $plan->trainer_id ?? $this->trainerId(), 'title' => $this->title, 'goal' => $this->goal,
                'level' => $this->level, 'duration_weeks' => $this->duration_weeks, 'start_date' => $this->start_date,
                'notes' => $this->notes, 'is_active' => true,
            ])->save();

            // Only one active PT workout plan per client
            PtWorkoutPlan::where('member_id', $this->member->id)->where('id', '!=', $plan->id)->update(['is_active' => false]);

            $plan->days()->delete();
            $library = Exercise::pluck('id', 'name');
            foreach ($this->days as $dow => $day) {
                if (! $day['is_rest'] && empty($day['exercises']) && ! $day['focus']) {
                    continue;
                }
                $d = $plan->days()->create(['day_of_week' => $dow, 'focus' => $day['is_rest'] ? ($day['focus'] ?: 'Rest / Mobility') : $day['focus'], 'is_rest' => $day['is_rest']]);
                foreach (array_values($day['exercises']) as $i => $ex) {
                    $d->exercises()->create(collect($ex)->map(fn ($v) => $v === '' ? null : $v)->all() + ['sort' => $i, 'exercise_id' => $library[$ex['name']] ?? null]);
                }
            }
            $this->viewPlanId = $plan->id;
        });

        $this->editing = false;
        $this->toast('Workout plan saved.');
    }

    public function render()
    {
        $plans = PtWorkoutPlan::where('member_id', $this->member->id)->latest('start_date')->latest('id')->get();
        $plan = $plans->firstWhere('id', (int) $this->viewPlanId) ?? $plans->firstWhere('is_active', true) ?? $plans->first();
        $plan?->load('days.exercises', 'trainer.user');

        return view('livewire.pt.client.workout', [
            'plans' => $plans,
            'plan' => $plan,
            'library' => $this->editing ? Exercise::orderBy('name')->get(['name', 'muscle_group']) : collect(),
            'canCoach' => $this->canCoach(),
        ]);
    }
}
