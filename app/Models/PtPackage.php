<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtPackage extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array { return ['price' => 'decimal:2', 'is_active' => 'boolean']; }

    public function subscriptions(): HasMany { return $this->hasMany(PtSubscription::class); }

    public function pricePerSession(): float
    {
        return $this->sessions_count ? round((float) $this->price / $this->sessions_count, 2) : 0;
    }
}
