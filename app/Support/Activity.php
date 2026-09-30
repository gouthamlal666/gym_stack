<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Activity
{
    /** Set true to silence logging (e.g. while seeding). */
    public static bool $disabled = false;

    public static function log(string $action, string $description, ?Model $subject = null, ?array $properties = null, $user = null): void
    {
        if (static::$disabled) {
            return;
        }
        $user ??= Auth::user();

        ActivityLog::create([
            'gym_id' => $subject->gym_id ?? $user?->gym_id,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()?->ip(),
        ]);
    }
}
