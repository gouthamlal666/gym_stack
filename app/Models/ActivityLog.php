<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use BelongsToGym;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array { return ['properties' => 'array']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function subject(): MorphTo { return $this->morphTo(); }
}
