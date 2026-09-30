<?php

namespace App\Notifications;

use App\Models\Membership;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Renewal reminder for a membership that is about to expire ("expiring") or has lapsed ("expired"). */
class MembershipReminder extends Notification
{
    public function __construct(public Membership $membership, public string $type) {}

    public function via($notifiable): array
    {
        return ['mail', 'whatsapp'];
    }

    public static function typeFor(Membership $m): string
    {
        return $m->end_date->lt(today()) ? 'expired' : 'expiring';
    }

    public function text(): string
    {
        $m = $this->membership;
        $name = $m->member->first_name;
        $gym = $m->member->gym->name;
        $date = $m->end_date->format('d M Y');

        if ($this->type === 'expired') {
            return "Hi {$name}, your {$m->plan->name} membership at {$gym} expired on {$date}. We miss you! 💪 "
                .'Renew today at the front desk to get back on track. Reply to this message if you need help.';
        }

        $days = $m->daysLeft();
        $when = $days === 0 ? 'today' : ($days === 1 ? 'tomorrow' : "in {$days} days");

        return "Hi {$name}, your {$m->plan->name} membership at {$gym} expires {$when} ({$date}). "
            .'Renew now to keep your training uninterrupted. Reply to this message or visit the front desk. 💪';
    }

    public function toMail($notifiable): MailMessage
    {
        $m = $this->membership;
        $gym = $m->member->gym;

        return (new MailMessage)
            ->subject($this->type === 'expired'
                ? "Your {$gym->name} membership has expired"
                : "Your {$gym->name} membership expires on {$m->end_date->format('d M Y')}")
            ->greeting("Hi {$m->member->first_name},")
            ->line($this->text())
            ->line('Plan: '.$m->plan->name.' · Ends: '.$m->end_date->format('d M Y'))
            ->line($gym->phone ? "Questions? Call us on {$gym->phone}." : 'See you at the gym!')
            ->salutation("— Team {$gym->name}");
    }

    public function toWhatsApp($notifiable): WhatsAppMessage
    {
        $m = $this->membership;
        $client = \App\Services\WhatsApp\WhatsAppClient::forGym($m->member->gym);
        $template = $client->config()["template_{$this->type}"] ?? null;

        // Template body params: {{1}} name, {{2}} plan, {{3}} end date, {{4}} gym name
        return new WhatsAppMessage($this->text(), $template ?: null, [
            $m->member->first_name, $m->plan->name, $m->end_date->format('d M Y'), $m->member->gym->name,
        ]);
    }
}
