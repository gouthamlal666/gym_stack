<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtGoal extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'target_date' => 'date', 'start_value' => 'float', 'target_value' => 'float'];
    }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function trainer(): BelongsTo { return $this->belongsTo(Trainer::class); }

    /** Latest measured value of the tracked metric, from the member's body assessments. */
    public function currentValue(): ?float
    {
        $latest = BodyAssessment::where('member_id', $this->member_id)
            ->whereNotNull($this->metric)
            ->latest('assessed_on')->latest('id')->value($this->metric);

        return $latest !== null ? (float) $latest : null;
    }

    public function progressPercent(): int
    {
        $current = $this->currentValue();
        $span = $this->target_value - $this->start_value;
        if ($current === null || $span == 0) {
            return 0;
        }

        return (int) max(0, min(100, round(($current - $this->start_value) / $span * 100)));
    }

    public function typeLabel(): string { return config("gym.goal_types.{$this->goal_type}", $this->goal_type); }
}
