<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtSession extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    public const STATUSES = [
        'scheduled' => 'Scheduled', 'in_progress' => 'In progress', 'completed' => 'Completed',
        'cancelled' => 'Cancelled', 'no_show' => 'No-show',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime', 'started_at' => 'datetime', 'ended_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo { return $this->belongsTo(PtSubscription::class, 'pt_subscription_id'); }
    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function trainer(): BelongsTo { return $this->belongsTo(Trainer::class); }
    public function exercises(): HasMany { return $this->hasMany(PtSessionExercise::class)->orderBy('sort'); }

    public function endsAt() { return $this->scheduled_at->copy()->addMinutes($this->duration_minutes); }

    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }

    public function actualMinutes(): ?int
    {
        return $this->started_at && $this->ended_at ? (int) $this->started_at->diffInMinutes($this->ended_at) : null;
    }

    public function activityLabel(): string { return 'PT session '.$this->scheduled_at?->format('d M Y H:i'); }
}
