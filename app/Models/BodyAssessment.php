<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BodyAssessment extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        $casts = ['assessed_on' => 'date', 'next_assessment_on' => 'date', 'is_initial' => 'boolean'];
        foreach (array_keys(config('gym.body_metrics')) as $metric) {
            $casts[$metric] = 'float';
        }
        $casts['height'] = 'float';

        return $casts;
    }

    protected static function booted(): void
    {
        static::saving(function (BodyAssessment $a) {
            if ($a->weight && $a->height) {
                $meters = $a->height / 100;
                $a->bmi = round($a->weight / ($meters * $meters), 1);
            }
        });
    }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function trainer(): BelongsTo { return $this->belongsTo(Trainer::class); }
    public function photos(): HasMany { return $this->hasMany(ProgressPhoto::class); }

    public function activityLabel(): string { return 'Body assessment '.$this->assessed_on?->format('d M Y'); }
}
