<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Invoice extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date', 'due_date' => 'date', 'last_reminder_at' => 'datetime',
            'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'tax_rate' => 'decimal:2', 'tax_amount' => 'decimal:2',
            'total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'amount_refunded' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function billable(): MorphTo { return $this->morphTo(); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class)->latest('paid_at'); }

    public function balance(): float
    {
        return round(max(0, (float) $this->total - (float) $this->amount_paid), 2);
    }

    public function netPaid(): float
    {
        return round((float) $this->amount_paid - (float) $this->amount_refunded, 2);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['unpaid', 'partial']) && $this->due_date->isPast();
    }

    public function refreshStatus(): void
    {
        $this->status = match (true) {
            $this->status === 'void' => 'void',
            (float) $this->amount_refunded > 0 && $this->netPaid() <= 0 => 'refunded',
            (float) $this->amount_paid >= (float) $this->total => 'paid',
            (float) $this->amount_paid > 0 => 'partial',
            default => 'unpaid',
        };
        $this->save();
    }
}
