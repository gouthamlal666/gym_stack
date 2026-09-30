<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array { return ['paid_at' => 'datetime', 'amount' => 'decimal:2']; }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    public function methodLabel(): string { return config("gym.payment_methods.{$this->method}", $this->method); }
}
