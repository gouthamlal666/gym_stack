<?php

namespace App\Notifications;

use App\Models\PtSession;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionReminder extends Notification
{
    public function __construct(public PtSession $session) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: PT session '.$this->session->scheduled_at->format('D d M, h:i A'))
            ->line('Your personal training session with '.$this->session->trainer->name.' is coming up.')
            ->line('When: '.$this->session->scheduled_at->format('l d M Y, h:i A'))
            ->line('Focus: '.($this->session->focus ?: 'As planned by your trainer'));
    }
}
