<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtWorkoutExercise extends Model
{
    protected $guarded = ['id'];

    public function day(): BelongsTo { return $this->belongsTo(PtWorkoutDay::class, 'pt_workout_day_id'); }
    public function exercise(): BelongsTo { return $this->belongsTo(Exercise::class); }
}
