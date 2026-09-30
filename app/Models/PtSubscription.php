<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PtSubscription extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'expiry_date' => 'date', 'price' => 'decimal:2', 'discount' => 'decimal:2'];
    }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function package(): BelongsTo { return $this->belongsTo(PtPackage::class, 'pt_package_id'); }
    public function trainer(): BelongsTo { return $this->belongsTo(Trainer::class); }
    public function sessions(): HasMany { return $this->hasMany(PtSession::class); }
    public function invoice(): MorphOne { return $this->morphOne(Invoice::class, 'billable'); }

    /** Sessions that count against the package (completed + optionally no-shows). */
    public function consumedSessions(): int
    {
        $statuses = config('gym.no_show_consumes_session') ? ['completed', 'no_show'] : ['completed'];

        return $this->sessions()->whereIn('status', $statuses)->count();
    }

    public function completedSessions(): int { return $this->sessions()->where('status', 'completed')->count(); }

    public function bookedSessions(): int { return $this->sessions()->whereIn('status', ['scheduled', 'in_progress'])->count(); }

    public function remainingSessions(): int { return max(0, $this->total_sessions - $this->consumedSessions()); }

    /** Remaining sessions that are not already booked. */
    public function bookableSessions(): int { return max(0, $this->remainingSessions() - $this->bookedSessions()); }

    public function progressPercent(): int
    {
        return $this->total_sessions ? (int) round($this->consumedSessions() / $this->total_sessions * 100) : 0;
    }

    public function isUsable(): bool
    {
        return $this->status === 'active' && ! $this->expiry_date->isPast() && $this->bookableSessions() > 0;
    }

    public function activityLabel(): string { return ($this->package?->name ?? 'PT package').' for member #'.$this->member_id; }
}
