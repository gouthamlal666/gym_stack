<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtWorkoutPlan extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array { return ['start_date' => 'date', 'is_active' => 'boolean']; }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function trainer(): BelongsTo { return $this->belongsTo(Trainer::class); }
    public function days(): HasMany { return $this->hasMany(PtWorkoutDay::class)->orderBy('day_of_week'); }

    public function endDate() { return $this->start_date->copy()->addWeeks($this->duration_weeks); }

    public function currentWeek(): int
    {
        return max(1, min($this->duration_weeks, (int) floor($this->start_date->diffInDays(now()) / 7) + 1));
    }
}
