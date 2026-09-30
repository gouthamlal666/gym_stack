<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminder extends Notification
{
    public function __construct(public Invoice $invoice) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        $gym = $this->invoice->gym;

        return (new MailMessage)
            ->subject("Payment reminder: invoice {$this->invoice->number}")
            ->greeting("Hi {$this->invoice->member->first_name},")
            ->line('A balance of '.Money::format($this->invoice->balance(), $gym->currency_symbol)." is due on invoice {$this->invoice->number}.")
            ->line('Due date: '.$this->invoice->due_date->format('d M Y'))
            ->line("Thank you for training with {$gym->name}!");
    }
}
