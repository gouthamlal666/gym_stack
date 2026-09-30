<?php

use App\Models\Invoice;
use App\Models\Membership;
use App\Models\PtSession;
use App\Notifications\PaymentReminder;
use App\Notifications\SessionReminder;
use App\Services\MembershipService;
use App\Services\PtService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;

Artisan::command('gym:refresh-statuses', function () {
    $result = MembershipService::refreshStatuses();
    $result['pt_expired'] = PtService::expireSubscriptions();
    $this->info(json_encode($result));
})->purpose('Activate, unfreeze and expire memberships and PT packages');

Artisan::command('gym:send-reminders', function () {
    $sent = 0;

    // PT session reminders ~24h ahead
    PtSession::withoutGlobalScopes()->with('member', 'trainer.user')
        ->where('status', 'scheduled')->whereNull('reminder_sent_at')
        ->whereBetween('scheduled_at', [now(), now()->addDay()])
        ->each(function (PtSession $s) use (&$sent) {
            if ($s->member->email) {
                Notification::route('mail', $s->member->email)->notify(new SessionReminder($s));
                $sent++;
            }
            $s->update(['reminder_sent_at' => now()]);
        });

    // Payment reminders for overdue invoices, at most every 3 days
    Invoice::withoutGlobalScopes()->with('member', 'gym')
        ->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())
        ->where(fn ($q) => $q->whereNull('last_reminder_at')->orWhere('last_reminder_at', '<', now()->subDays(3)))
        ->each(function (Invoice $i) use (&$sent) {
            if ($i->member->email) {
                Notification::route('mail', $i->member->email)->notify(new PaymentReminder($i));
                $sent++;
            }
            $i->update(['last_reminder_at' => now()]);
        });

    $this->info("Sent {$sent} reminder(s).");
})->purpose('Send PT session and payment reminders');

Artisan::command('gym:membership-reminders', function (App\Services\ReminderService $reminders) {
    $this->info(json_encode($reminders->runAutomatic()));
})->purpose('WhatsApp + email renewal reminders for expiring and expired memberships');

Schedule::command('gym:refresh-statuses')->dailyAt('00:05');
Schedule::command('gym:send-reminders')->hourly();
Schedule::command('gym:membership-reminders')->dailyAt('09:30'); // after statuses refresh, at a sociable hour
