<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Membership;
use App\Models\NotificationLog;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\MembershipReminder;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ReminderService
{
    public const CHANNELS = ['whatsapp' => 'WhatsApp', 'mail' => 'Email'];

    public static function defaults(): array
    {
        return ['auto' => true, 'channels' => ['whatsapp', 'mail'], 'expiring_days' => [7, 3, 1], 'expired_days' => [1, 7]];
    }

    public static function settings(Gym $gym): array
    {
        return array_merge(self::defaults(), $gym->setting('reminders', []));
    }

    /**
     * Sends a renewal reminder on each channel and logs every attempt.
     *
     * @return array<string, NotificationLog>
     */
    public function send(Membership $membership, array $channels, bool $automatic = false): array
    {
        $membership->loadMissing('member.gym', 'plan');
        $member = $membership->member;
        $type = MembershipReminder::typeFor($membership);
        $notification = new MembershipReminder($membership, $type);
        $results = [];

        foreach (array_intersect($channels, array_keys(self::CHANNELS)) as $channel) {
            $recipient = $channel === 'mail' ? $member->email : WhatsAppClient::forGym($member->gym)->normalize($member->phone);
            $status = 'skipped';
            $error = null;

            if (! $recipient) {
                $error = $channel === 'mail' ? 'No email address' : 'No valid phone number';
            } else {
                try {
                    WhatsAppChannel::$lastStatus = null;
                    Notification::sendNow($member, $notification, [$channel]);
                    $status = $channel === 'whatsapp' ? (WhatsAppChannel::$lastStatus ?? 'sent') : 'sent';
                } catch (Throwable $e) {
                    $status = 'failed';
                    $error = mb_substr($e->getMessage(), 0, 500);
                    report($e);
                }
            }

            $results[$channel] = NotificationLog::create([
                'gym_id' => $member->gym_id,
                'member_id' => $member->id,
                'membership_id' => $membership->id,
                'sent_by' => $automatic ? null : Auth::id(),
                'type' => "membership_{$type}",
                'channel' => $channel,
                'recipient' => $recipient,
                'status' => $status,
                'error' => $error,
            ]);
        }

        return $results;
    }

    /** Scheduled run: remind members N days before expiry and N days after lapsing, once per day per membership. */
    public function runAutomatic(): array
    {
        $stats = ['memberships' => 0, 'sent' => 0, 'failed' => 0];

        Gym::where('status', 'active')->each(function (Gym $gym) use (&$stats) {
            $cfg = self::settings($gym);
            if (! $cfg['auto'] || empty($cfg['channels'])) {
                return;
            }

            $expiring = Membership::withoutGlobalScopes()->with('member.gym', 'plan')->where('gym_id', $gym->id)
                ->where('status', 'active')
                ->where(fn ($q) => collect($cfg['expiring_days'])->each(fn ($d) => $q->orWhereDate('end_date', today()->addDays((int) $d))))
                ->get();

            $expired = Membership::withoutGlobalScopes()->with('member.gym', 'plan')->where('gym_id', $gym->id)
                ->lapsed()
                ->where(fn ($q) => collect($cfg['expired_days'])->each(fn ($d) => $q->orWhereDate('end_date', today()->subDays((int) $d))))
                ->get();

            foreach ($expiring->merge($expired) as $membership) {
                if (! $membership->member || $membership->member->status !== 'active') {
                    continue;
                }
                $alreadyToday = NotificationLog::withoutGlobalScopes()->where('membership_id', $membership->id)
                    ->whereNull('sent_by')->whereDate('created_at', today())->exists();
                if ($alreadyToday) {
                    continue;
                }
                $stats['memberships']++;
                foreach ($this->send($membership, $cfg['channels'], automatic: true) as $log) {
                    $log->succeeded() ? $stats['sent']++ : ($log->status === 'failed' ? $stats['failed']++ : null);
                }
            }
        });

        return $stats;
    }
}
