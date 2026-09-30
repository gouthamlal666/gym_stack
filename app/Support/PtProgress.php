<?php

namespace App\Support;

use App\Models\BodyAssessment;
use App\Models\Member;
use App\Models\PtWorkoutPlan;
use Illuminate\Support\Collection;

/** Builds the progress analytics shown to PT trainers and PT customers. */
class PtProgress
{
    public static function assessments(Member $member): Collection
    {
        return BodyAssessment::where('member_id', $member->id)->orderBy('assessed_on')->orderBy('id')->get();
    }

    /** Rows of [metric, label, unit, a, b, change, better] comparing two assessments. */
    public static function compare(?BodyAssessment $a, ?BodyAssessment $b): array
    {
        if (! $a || ! $b) {
            return [];
        }
        $rows = [];
        foreach (config('gym.body_metrics') as $key => $meta) {
            if ($a->$key === null && $b->$key === null) {
                continue;
            }
            $rows[] = [
                'metric' => $key, 'label' => $meta['label'], 'unit' => $meta['unit'], 'better' => $meta['better'] ?? 'neutral',
                'a' => $a->$key, 'b' => $b->$key,
                'change' => $a->$key !== null && $b->$key !== null ? round($b->$key - $a->$key, 1) : null,
            ];
        }

        return $rows;
    }

    public static function lineChart(Collection $assessments, array $metrics): array
    {
        return [
            'type' => 'line',
            'labels' => $assessments->map(fn ($a) => $a->assessed_on->format('d M'))->values()->all(),
            'datasets' => collect($metrics)->map(fn ($m) => [
                'label' => config("gym.body_metrics.$m.label").' ('.config("gym.body_metrics.$m.unit").')',
                'data' => $assessments->pluck($m)->values()->all(),
            ])->all(),
        ];
    }

    /** Everything needed for the PT summary card / PT customer dashboard. */
    public static function summary(Member $member): array
    {
        $sub = $member->activePtSubscription()->with('package', 'trainer.user')->first()
            ?? $member->ptSubscriptions()->with('package', 'trainer.user')->first();
        $assessments = self::assessments($member);
        $first = $assessments->first();
        $latest = $assessments->last();
        $goal = $member->goals()->where('status', 'active')->latest()->first();
        $plan = PtWorkoutPlan::with('days')->where('member_id', $member->id)->where('is_active', true)->latest()->first();

        return [
            'subscription' => $sub,
            'completed' => $sub?->completedSessions() ?? 0,
            'remaining' => $sub?->remainingSessions() ?? 0,
            'goal' => $goal,
            'first' => $first,
            'latest' => $latest,
            'bodyRows' => collect(self::compare($first, $latest))->whereIn('metric', ['weight', 'body_fat', 'waist', 'muscle_mass'])->values()->all(),
            'nextSession' => $member->ptSessions()->with('trainer.user')->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now()->startOfDay())->orderBy('scheduled_at')->first(),
            'nextAssessment' => $latest?->next_assessment_on,
            'todayWorkout' => $plan?->days->firstWhere('day_of_week', now()->dayOfWeekIso),
            'plan' => $plan,
            'weightChart' => $assessments->count() > 1 ? self::lineChart($assessments, ['weight']) : null,
        ];
    }
}
