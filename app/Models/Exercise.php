<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use BelongsToGym;

    /** Global (gym_id = null) exercises are shared with every gym. */
    protected static bool $includesGlobalRecords = true;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Exercise $exercise) {
            if (! array_key_exists('gym_id', $exercise->getAttributes())) {
                $exercise->gym_id = static::currentGymId();
            }
        });
    }
}
