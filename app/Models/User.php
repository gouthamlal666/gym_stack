<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, LogsActivity, Notifiable;

    protected $guarded = ['id', 'remember_token'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function gym(): BelongsTo { return $this->belongsTo(Gym::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function member(): HasOne { return $this->hasOne(Member::class); }
    public function trainer(): HasOne { return $this->hasOne(Trainer::class); }

    public function hasRole(string ...$roles): bool { return in_array($this->role, $roles, true); }
    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isMember(): bool { return $this->role === 'member'; }
    public function isTrainer(): bool { return $this->role === 'trainer'; }
    public function isStaff(): bool { return in_array($this->role, config('gym.staff_roles'), true); }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Keys contain dots (e.g. "members.view"), so read the map directly rather than via dot-notation.
        return in_array($this->role, config('gym.permissions')[$permission] ?? [], true);
    }

    public function roleLabel(): string { return config("gym.roles.{$this->role}", $this->role); }

    public function homeRoute(): string
    {
        return match (true) {
            $this->isSuperAdmin() => route('admin.dashboard'),
            $this->isMember() => route('portal.dashboard'),
            default => route('dashboard'),
        };
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    }
}
