<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpCodeNotification extends Notification
{
    public function __construct(public string $code, public string $purpose) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your '.config('app.name').' verification code')
            ->line('Use this code to '.($this->purpose === 'two_factor' ? 'complete your sign-in' : 'sign in').':')
            ->line("**{$this->code}**")
            ->line('It expires in '.config('gym.otp.ttl_minutes').' minutes. If you did not request it, you can ignore this email.');
    }
}
