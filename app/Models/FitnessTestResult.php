<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FitnessTestResult extends Model
{
    protected $guarded = ['id'];

    public function assessment(): BelongsTo { return $this->belongsTo(FitnessAssessment::class, 'fitness_assessment_id'); }
}
