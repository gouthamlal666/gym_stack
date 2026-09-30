<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use BelongsToGym;

    protected $guarded = ['id'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function membership(): BelongsTo { return $this->belongsTo(Membership::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }

    public function succeeded(): bool { return in_array($this->status, ['sent', 'logged']); }
}
