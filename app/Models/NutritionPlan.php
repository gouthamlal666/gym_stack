<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NutritionPlan extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array { return ['start_date' => 'date', 'is_active' => 'boolean', 'water_liters' => 'float']; }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function trainer(): BelongsTo { return $this->belongsTo(Trainer::class); }
    public function meals(): HasMany { return $this->hasMany(NutritionMeal::class)->orderBy('sort'); }
}
