<?php

namespace App\Models\Concerns;

use App\Support\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                if (! Auth::hasUser()) {
                    return;
                }
                $changes = $event === 'updated'
                    ? collect($model->getChanges())->except(['updated_at', 'password', 'remember_token'])->all()
                    : null;
                if ($event === 'updated' && empty($changes)) {
                    return;
                }
                $label = Str::headline(class_basename($model));
                Activity::log("{$event}", "{$label} {$event}: {$model->activityLabel()}", $model, $changes ? ['changes' => $changes] : null);
            });
        }
    }

    public function activityLabel(): string
    {
        return (string) ($this->name ?? $this->title ?? $this->number ?? $this->receipt_number ?? '#'.$this->getKey());
    }
}
