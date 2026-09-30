<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToGym;

    protected $guarded = ['id'];

    protected function casts(): array { return ['check_in_at' => 'datetime', 'check_out_at' => 'datetime']; }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
