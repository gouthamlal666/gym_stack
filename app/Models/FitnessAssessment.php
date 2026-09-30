<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FitnessAssessment extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array { return ['assessed_on' => 'date']; }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function trainer(): BelongsTo { return $this->belongsTo(Trainer::class); }
    public function results(): HasMany { return $this->hasMany(FitnessTestResult::class); }

    public function result(string $key): ?float
    {
        $r = $this->results->firstWhere('test_key', $key);

        return $r ? (float) $r->value : null;
    }

    public function overallScore(): ?float
    {
        $values = collect(array_keys(config('gym.fitness_ratings')))->map(fn ($k) => $this->$k)->filter();

        return $values->isEmpty() ? null : round($values->avg(), 1);
    }

    public function activityLabel(): string { return 'Fitness assessment '.$this->assessed_on?->format('d M Y'); }
}
