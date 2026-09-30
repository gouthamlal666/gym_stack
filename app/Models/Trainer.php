<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trainer extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'specializations' => 'array',
            'certifications' => 'array',
            'availability' => 'array',
            'is_pt_trainer' => 'boolean',
            'commission_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function members(): HasMany { return $this->hasMany(Member::class); }
    public function ptSubscriptions(): HasMany { return $this->hasMany(PtSubscription::class); }
    public function ptSessions(): HasMany { return $this->hasMany(PtSession::class); }

    public function getNameAttribute(): string { return $this->user?->name ?? 'Trainer #'.$this->id; }

    public function activityLabel(): string { return $this->name; }

    /** PT clients = members with an active PT subscription under this trainer, or directly assigned PT members. */
    public function ptClientIds(): array
    {
        return Member::query()
            ->where('training_type', 'pt')
            ->where(fn ($q) => $q->where('trainer_id', $this->id)
                ->orWhereHas('ptSubscriptions', fn ($s) => $s->where('trainer_id', $this->id)->where('status', 'active')))
            ->pluck('id')->all();
    }

    public function worksOn(int $dayOfWeek): ?array
    {
        return $this->availability[$dayOfWeek] ?? null;
    }
}
