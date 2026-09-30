<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionMeal extends Model
{
    protected $guarded = ['id'];

    public function plan(): BelongsTo { return $this->belongsTo(NutritionPlan::class, 'nutrition_plan_id'); }

    public function label(): string { return config("gym.meal_types.{$this->meal_type}", $this->meal_type); }
}
