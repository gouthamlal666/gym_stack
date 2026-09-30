<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDocument extends Model
{
    use BelongsToGym;

    protected $guarded = ['id'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
