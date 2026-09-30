<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function members(): HasMany { return $this->hasMany(Member::class); }
    public function staff(): HasMany { return $this->hasMany(User::class); }
}
