<?php

namespace App\Notifications\Channels;

use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Notifications\Notification;
use RuntimeException;

class WhatsAppChannel
{
    /** Status of the last send ("sent" or "logged"), read by the reminder service. */
    public static ?string $lastStatus = null;

    public function send($notifiable, Notification $notification): void
    {
        $client = WhatsAppClient::forGym($notifiable->gym);
        $to = $client->normalize($notifiable->routeNotificationFor('whatsapp', $notification));
        if (! $to) {
            throw new RuntimeException('No valid phone number.');
        }
        static::$lastStatus = $client->send($to, $notification->toWhatsApp($notifiable));
    }
}
