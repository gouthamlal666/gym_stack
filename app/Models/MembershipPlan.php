<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'admission_fee' => 'decimal:2', 'is_trial' => 'boolean', 'is_active' => 'boolean'];
    }

    public function memberships(): HasMany { return $this->hasMany(Membership::class); }

    public function cycleLabel(): string { return config("gym.billing_cycles.{$this->billing_cycle}.label", $this->billing_cycle); }

    public function dailyRate(): float { return $this->duration_days > 0 ? (float) $this->price / $this->duration_days : 0; }
}
