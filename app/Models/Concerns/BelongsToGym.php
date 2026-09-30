<?php

namespace App\Models\Concerns;

use App\Models\Gym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Row-level multi-tenancy: every query is limited to the signed-in user's gym,
 * and new records are stamped with it automatically.
 */
trait BelongsToGym
{
    public static function bootBelongsToGym(): void
    {
        static::addGlobalScope('gym', function (Builder $query) {
            $gymId = static::currentGymId();
            if ($gymId === null) {
                return;
            }
            $column = $query->getModel()->qualifyColumn('gym_id');
            if (static::$includesGlobalRecords ?? false) {
                $query->where(fn ($q) => $q->where($column, $gymId)->orWhereNull($column));
            } else {
                $query->where($column, $gymId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->gym_id) && ! (static::$includesGlobalRecords ?? false)) {
                $model->gym_id = static::currentGymId();
            }
        });
    }

    protected static function currentGymId(): ?int
    {
        if (! Auth::hasUser()) {
            return null;
        }

        return Auth::user()->gym_id;
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}
