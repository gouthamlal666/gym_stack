<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtWorkoutDay extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['is_rest' => 'boolean']; }

    public function plan(): BelongsTo { return $this->belongsTo(PtWorkoutPlan::class, 'pt_workout_plan_id'); }
    public function exercises(): HasMany { return $this->hasMany(PtWorkoutExercise::class)->orderBy('sort'); }

    public function dayName(): string { return config("gym.weekdays.{$this->day_of_week}"); }
}
