<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Gym extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'trial_ends_at' => 'date', 'tax_rate' => 'decimal:2'];
    }

    public function branches(): HasMany { return $this->hasMany(Branch::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function members(): HasMany { return $this->hasMany(Member::class); }

    public function isActive(): bool { return $this->status === 'active'; }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function setting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }
}
