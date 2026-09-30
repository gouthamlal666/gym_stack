<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillingService
{
    /**
     * @param  array<int, array{description: string, quantity?: int, unit_price: float}>  $items
     */
    public function createInvoice(Member $member, array $items, float $discount = 0, ?Model $billable = null,
        ?string $dueDate = null, ?float $taxRate = null, ?string $notes = null): Invoice
    {
        $gym = $member->gym;
        $taxRate ??= (float) $gym->tax_rate;

        return DB::transaction(function () use ($member, $items, $discount, $billable, $dueDate, $taxRate, $notes, $gym) {
            $subtotal = collect($items)->sum(fn ($i) => ($i['quantity'] ?? 1) * $i['unit_price']);
            $discount = min($discount, $subtotal);
            $taxable = $subtotal - $discount;
            $tax = round($taxable * $taxRate / 100, 2);

            $invoice = Invoice::create([
                'gym_id' => $member->gym_id,
                'branch_id' => $member->branch_id,
                'member_id' => $member->id,
                'billable_type' => $billable?->getMorphClass(),
                'billable_id' => $billable?->getKey(),
                'number' => $this->nextNumber($gym->id, $gym->invoice_prefix, Invoice::class, 'number'),
                'issue_date' => today(),
                'due_date' => $dueDate ?? today()->addDays(7),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $tax,
                'total' => round($taxable + $tax, 2),
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $qty = $item['quantity'] ?? 1;
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $qty,
                    'unit_price' => $item['unit_price'],
                    'amount' => $qty * $item['unit_price'],
                ]);
            }

            return $invoice;
        });
    }

    public function recordPayment(Invoice $invoice, float $amount, string $method, ?string $reference = null,
        ?string $notes = null, $paidAt = null): Payment
    {
        if ($amount <= 0 || $amount > $invoice->balance() + 0.001) {
            throw new InvalidArgumentException('Payment must be between 0 and the outstanding balance.');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $reference, $notes, $paidAt) {
            $payment = $invoice->payments()->create([
                'gym_id' => $invoice->gym_id,
                'branch_id' => $invoice->branch_id,
                'member_id' => $invoice->member_id,
                'received_by' => Auth::id(),
                'type' => 'payment',
                'receipt_number' => $this->nextNumber($invoice->gym_id, 'RCPT', Payment::class, 'receipt_number'),
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'notes' => $notes,
                'paid_at' => $paidAt ?? now(),
            ]);
            $invoice->increment('amount_paid', $amount);
            $invoice->refresh()->refreshStatus();

            return $payment;
        });
    }

    public function refund(Invoice $invoice, float $amount, string $method, string $reason): Payment
    {
        if ($amount <= 0 || $amount > $invoice->netPaid() + 0.001) {
            throw new InvalidArgumentException('Refund cannot exceed the amount paid.');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $reason) {
            $refund = $invoice->payments()->create([
                'gym_id' => $invoice->gym_id,
                'branch_id' => $invoice->branch_id,
                'member_id' => $invoice->member_id,
                'received_by' => Auth::id(),
                'type' => 'refund',
                'receipt_number' => $this->nextNumber($invoice->gym_id, 'RFND', Payment::class, 'receipt_number'),
                'amount' => $amount,
                'method' => $method,
                'notes' => $reason,
                'paid_at' => now(),
            ]);
            $invoice->increment('amount_refunded', $amount);
            $invoice->refresh()->refreshStatus();
            Activity::log('refund', "Refunded {$amount} on invoice {$invoice->number}", $invoice, ['reason' => $reason]);

            return $refund;
        });
    }

    public function void(Invoice $invoice): void
    {
        if ((float) $invoice->amount_paid > 0) {
            throw new InvalidArgumentException('Only invoices without payments can be voided. Refund instead.');
        }
        $invoice->update(['status' => 'void']);
    }

    private function nextNumber(int $gymId, string $prefix, string $model, string $column): string
    {
        $count = $model::withoutGlobalScopes()->where('gym_id', $gymId)->where($column, 'like', "$prefix-%")->count();

        do {
            $number = $prefix.'-'.str_pad((string) ++$count, 6, '0', STR_PAD_LEFT);
        } while ($model::withoutGlobalScopes()->where('gym_id', $gymId)->where($column, $number)->exists());

        return $number;
    }
}
