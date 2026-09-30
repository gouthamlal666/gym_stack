<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Membership extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', 'end_date' => 'date', 'frozen_from' => 'date', 'frozen_until' => 'date',
            'cancelled_at' => 'datetime', 'price' => 'decimal:2', 'discount' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function plan(): BelongsTo { return $this->belongsTo(MembershipPlan::class, 'membership_plan_id'); }
    public function previous(): BelongsTo { return $this->belongsTo(Membership::class, 'previous_membership_id'); }
    public function invoice(): MorphOne { return $this->morphOne(Invoice::class, 'billable'); }

    public function reminderLogs(): HasMany { return $this->hasMany(NotificationLog::class)->latest(); }

    /** Expired memberships whose member has not renewed: the member's most recent membership is this expired one. */
    public function scopeLapsed(Builder $q): Builder
    {
        return $q->where('memberships.status', 'expired')->whereRaw(
            'memberships.id = (select m2.id from memberships m2 where m2.member_id = memberships.member_id '
            ."and m2.status != 'cancelled' order by m2.end_date desc, m2.id desc limit 1)"
        );
    }

    public function daysSinceExpiry(): int
    {
        return max(0, (int) $this->end_date->diffInDays(today()));
    }

    public function daysLeft(): int
    {
        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date, false));
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->status === 'active' && $this->daysLeft() <= $days;
    }

    public function activityLabel(): string { return ($this->plan?->name ?? 'Membership').' for member #'.$this->member_id; }
}
