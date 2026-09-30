<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtSessionExercise extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['weight' => 'decimal:2']; }

    public function session(): BelongsTo { return $this->belongsTo(PtSession::class, 'pt_session_id'); }
}
