<?php

namespace App\Services\WhatsApp;

use App\Models\Gym;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Sends WhatsApp messages for a gym.
 * Drivers: "log" (development — writes to the log) and "meta" (WhatsApp Business Cloud API).
 * Each gym stores its own WhatsApp Business credentials in settings.whatsapp; .env values are the fallback.
 */
class WhatsAppClient
{
    public function __construct(private Gym $gym) {}

    public static function forGym(Gym $gym): self
    {
        return new self($gym);
    }

    public function config(): array
    {
        $s = $this->gym->setting('whatsapp', []);
        $token = null;
        if (! empty($s['token'])) {
            try {
                $token = Crypt::decryptString($s['token']);
            } catch (\Throwable) {
                $token = null;
            }
        }

        return [
            'driver' => $s['driver'] ?? config('services.whatsapp.driver', 'log'),
            'phone_number_id' => $s['phone_number_id'] ?? config('services.whatsapp.phone_number_id'),
            'token' => $token ?? config('services.whatsapp.token'),
            'language' => $s['language'] ?? 'en',
            'country_code' => $s['country_code'] ?? '91',
            'template_expiring' => $s['template_expiring'] ?? null,
            'template_expired' => $s['template_expired'] ?? null,
            'api_version' => config('services.whatsapp.api_version', 'v21.0'),
        ];
    }

    public function driver(): string
    {
        return $this->config()['driver'];
    }

    /** Normalises a phone number to international digits, e.g. "+91 98470 12345" → "919847012345". */
    public function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        $digits = ltrim($digits, '0');
        if (strlen($digits) <= 10) {
            $digits = $this->config()['country_code'].$digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    /** Returns "sent" or "logged"; throws on failure. */
    public function send(string $to, WhatsAppMessage $message): string
    {
        $cfg = $this->config();

        if ($cfg['driver'] !== 'meta') {
            Log::info("[WhatsApp:{$this->gym->name}] to {$to}: {$message->text}", ['template' => $message->template]);

            return 'logged';
        }

        if (! $cfg['phone_number_id'] || ! $cfg['token']) {
            throw new RuntimeException('WhatsApp is not configured (phone number ID / access token missing).');
        }

        $payload = ['messaging_product' => 'whatsapp', 'to' => $to];
        if ($message->template) {
            $payload += ['type' => 'template', 'template' => [
                'name' => $message->template,
                'language' => ['code' => $message->language ?? $cfg['language']],
            ]];
            if ($message->templateParams) {
                $payload['template']['components'] = [[
                    'type' => 'body',
                    'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $message->templateParams),
                ]];
            }
        } else {
            $payload += ['type' => 'text', 'text' => ['preview_url' => false, 'body' => $message->text]];
        }

        $response = Http::withToken($cfg['token'])->acceptJson()->timeout(15)
            ->post("https://graph.facebook.com/{$cfg['api_version']}/{$cfg['phone_number_id']}/messages", $payload);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'WhatsApp API error (HTTP '.$response->status().')');
        }

        return 'sent';
    }

    /** Click-to-chat link staff can open to send the message from their own WhatsApp. */
    public function chatLink(?string $phone, string $text): ?string
    {
        $to = $this->normalize($phone);

        return $to ? 'https://wa.me/'.$to.'?text='.rawurlencode($text) : null;
    }
}
