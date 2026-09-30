<?php

namespace App\Livewire\Billing;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Invoice;
use App\Notifications\PaymentReminder;
use App\Services\BillingService;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class InvoiceShow extends Component
{
    use InteractsWithUi;

    public Invoice $invoice;
    public ?string $modal = null;

    public $amount = '';
    public string $method = 'cash';
    public ?string $reference = '';
    public ?string $notes = '';

    public function mount(Invoice $invoice): void
    {
        $this->requirePermission('billing.view');
        $this->invoice = $invoice;
    }

    public function openPayment(): void
    {
        $this->amount = $this->invoice->balance();
        $this->resetErrorBag();
        $this->modal = 'pay';
    }

    public function openRefund(): void
    {
        $this->amount = $this->invoice->netPaid();
        $this->notes = '';
        $this->resetErrorBag();
        $this->modal = 'refund';
    }

    public function pay(BillingService $billing): void
    {
        $this->requirePermission('billing.collect');
        $this->validate(['amount' => 'required|numeric|min:0.01', 'method' => 'required|in:'.implode(',', array_keys(config('gym.payment_methods')))]);
        try {
            $payment = $billing->recordPayment($this->invoice, (float) $this->amount, $this->method, $this->reference, $this->notes);
            $this->modal = null;
            $this->reset('reference', 'notes');
            $this->toast("Payment recorded · receipt {$payment->receipt_number}");
        } catch (InvalidArgumentException $e) {
            $this->addError('amount', $e->getMessage());
        }
    }

    public function refund(BillingService $billing): void
    {
        $this->requirePermission('billing.refund');
        $this->validate(['amount' => 'required|numeric|min:0.01', 'notes' => 'required|string|max:300']);
        try {
            $billing->refund($this->invoice, (float) $this->amount, $this->method, $this->notes);
            $this->modal = null;
            $this->toast('Refund recorded.');
        } catch (InvalidArgumentException $e) {
            $this->addError('amount', $e->getMessage());
        }
    }

    public function void(BillingService $billing): void
    {
        $this->requirePermission('billing.refund');
        try {
            $billing->void($this->invoice);
            $this->toast('Invoice voided.');
        } catch (InvalidArgumentException $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function remind(): void
    {
        if (! $this->invoice->member->email) {
            $this->toast('Member has no email address.', 'error');

            return;
        }
        Notification::route('mail', $this->invoice->member->email)->notify(new PaymentReminder($this->invoice));
        $this->invoice->update(['last_reminder_at' => now()]);
        $this->toast('Reminder sent.');
    }

    public function render()
    {
        $this->invoice->load('items', 'member', 'payments.receiver', 'billable');

        return view('livewire.billing.invoice-show')->title('Invoice '.$this->invoice->number);
    }
}
