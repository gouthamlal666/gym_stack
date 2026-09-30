<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Member extends Model
{
    use BelongsToGym, LogsActivity, Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array { return ['date_of_birth' => 'date', 'joined_on' => 'date']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function trainer(): BelongsTo { return $this->belongsTo(Trainer::class); }
    public function documents(): HasMany { return $this->hasMany(MemberDocument::class); }
    public function memberships(): HasMany { return $this->hasMany(Membership::class)->latest('start_date'); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class)->latest('issue_date'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class)->latest('paid_at'); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class)->latest('check_in_at'); }
    public function ptSubscriptions(): HasMany { return $this->hasMany(PtSubscription::class)->latest('start_date'); }
    public function ptSessions(): HasMany { return $this->hasMany(PtSession::class); }
    public function bodyAssessments(): HasMany { return $this->hasMany(BodyAssessment::class); }
    public function progressPhotos(): HasMany { return $this->hasMany(ProgressPhoto::class); }
    public function fitnessAssessments(): HasMany { return $this->hasMany(FitnessAssessment::class); }
    public function workoutPlans(): HasMany { return $this->hasMany(PtWorkoutPlan::class); }
    public function nutritionPlans(): HasMany { return $this->hasMany(NutritionPlan::class); }
    public function goals(): HasMany { return $this->hasMany(PtGoal::class); }

    public function currentMembership(): HasOne
    {
        return $this->hasOne(Membership::class)->whereIn('status', ['active', 'frozen'])->latestOfMany('end_date');
    }

    public function activePtSubscription(): HasOne
    {
        return $this->hasOne(PtSubscription::class)->where('status', 'active')->latestOfMany('start_date');
    }

    public function reminderLogs(): HasMany { return $this->hasMany(NotificationLog::class)->latest(); }

    public function routeNotificationForMail(): ?string { return $this->email; }

    public function routeNotificationForWhatsapp(): ?string { return $this->phone; }

    public function scopePt(Builder $q): Builder { return $q->where('training_type', 'pt'); }

    public function isPt(): bool { return $this->training_type === 'pt'; }

    public function getNameAttribute(): string { return trim($this->first_name.' '.$this->last_name); }

    public function activityLabel(): string { return "{$this->name} ({$this->member_code})"; }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1));
    }

    public function outstandingBalance(): float
    {
        return (float) $this->invoices()->whereIn('status', ['unpaid', 'partial'])->get()->sum(fn ($i) => $i->balance());
    }

    /** Trainer who coaches this PT member: active PT subscription trainer, falling back to assigned trainer. */
    public function coachId(): ?int
    {
        return $this->activePtSubscription?->trainer_id ?? $this->trainer_id;
    }

    public static function nextCode(Gym $gym): string
    {
        $last = static::withoutGlobalScopes()->withTrashed()->where('gym_id', $gym->id)->max('id') ?? 0;

        return ($gym->member_prefix ?: 'MEM').'-'.str_pad((string) ($last + 1001), 5, '0', STR_PAD_LEFT);
    }
}
