<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\PtPackage;
use App\Models\PtSession;
use App\Models\PtSubscription;
use App\Models\Trainer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PtService
{
    public function __construct(private BillingService $billing) {}

    /** Sells a PT package: converts the member to a PT customer, creates the subscription and invoice. */
    public function enroll(Member $member, PtPackage $package, Trainer $trainer, string $startDate, float $discount = 0): Invoice
    {
        return DB::transaction(function () use ($member, $package, $trainer, $startDate, $discount) {
            $start = Carbon::parse($startDate);
            $sub = PtSubscription::create([
                'gym_id' => $member->gym_id,
                'member_id' => $member->id,
                'pt_package_id' => $package->id,
                'trainer_id' => $trainer->id,
                'total_sessions' => $package->sessions_count,
                'start_date' => $start,
                'expiry_date' => $start->copy()->addDays($package->validity_days),
                'price' => $package->price,
                'discount' => $discount,
            ]);

            $member->update(['training_type' => 'pt', 'trainer_id' => $trainer->id]);

            return $this->billing->createInvoice($member, [[
                'description' => "PT package: {$package->name} ({$package->sessions_count} sessions with {$trainer->name})",
                'unit_price' => (float) $package->price,
            ]], $discount, $sub);
        });
    }

    public function book(PtSubscription $sub, string $dateTime, ?string $focus = null, ?int $duration = null): PtSession
    {
        $at = Carbon::parse($dateTime);
        $duration ??= $sub->package->session_minutes;

        if (! $sub->isUsable()) {
            throw new InvalidArgumentException('This PT package has no bookable sessions left or has expired.');
        }
        if ($at->gt($sub->expiry_date->copy()->endOfDay())) {
            throw new InvalidArgumentException('Session date is after the package expiry ('.$sub->expiry_date->format('d M Y').').');
        }
        $this->assertSlotFree($sub->trainer, $at, $duration);

        return PtSession::create([
            'gym_id' => $sub->gym_id,
            'pt_subscription_id' => $sub->id,
            'member_id' => $sub->member_id,
            'trainer_id' => $sub->trainer_id,
            'scheduled_at' => $at,
            'duration_minutes' => $duration,
            'focus' => $focus,
        ]);
    }

    public function reschedule(PtSession $session, string $dateTime): void
    {
        $this->assertEditable($session);
        $at = Carbon::parse($dateTime);
        $this->assertSlotFree($session->trainer, $at, $session->duration_minutes, $session->id);
        $session->update(['scheduled_at' => $at, 'reschedule_count' => $session->reschedule_count + 1, 'reminder_sent_at' => null]);
    }

    public function cancel(PtSession $session, string $reason): void
    {
        $this->assertEditable($session);
        $session->update(['status' => 'cancelled', 'cancel_reason' => $reason]);
    }

    public function markNoShow(PtSession $session): void
    {
        $this->assertEditable($session);
        $session->update(['status' => 'no_show']);
        $this->closeIfFinished($session->subscription);
    }

    public function start(PtSession $session): void
    {
        $this->assertEditable($session);
        $session->update(['status' => 'in_progress', 'started_at' => now()]);
    }

    public function complete(PtSession $session, array $data): void
    {
        if (! in_array($session->status, ['scheduled', 'in_progress'])) {
            throw new InvalidArgumentException('Session is already closed.');
        }
        $session->update(array_merge($data, [
            'status' => 'completed',
            'started_at' => $session->started_at ?? $session->scheduled_at,
            'ended_at' => now()->max($session->started_at ?? $session->scheduled_at),
        ]));
        $this->closeIfFinished($session->subscription);
    }

    /** Available start times for a trainer on a date, in 30-min steps within availability hours. */
    public function availableSlots(Trainer $trainer, string $date, int $duration = 60): array
    {
        $day = Carbon::parse($date);
        $hours = $trainer->worksOn($day->dayOfWeekIso);
        if (! $hours) {
            return [];
        }

        $booked = PtSession::where('trainer_id', $trainer->id)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereDate('scheduled_at', $day)->get();

        $slots = [];
        $cursor = $day->copy()->setTimeFromTimeString($hours['start']);
        $close = $day->copy()->setTimeFromTimeString($hours['end']);
        while ($cursor->copy()->addMinutes($duration)->lte($close)) {
            $end = $cursor->copy()->addMinutes($duration);
            $clash = $booked->contains(fn ($s) => $cursor->lt($s->endsAt()) && $end->gt($s->scheduled_at));
            if (! $clash && $cursor->isFuture()) {
                $slots[] = $cursor->format('H:i');
            }
            $cursor->addMinutes(30);
        }

        return $slots;
    }

    private function assertSlotFree(Trainer $trainer, Carbon $at, int $duration, ?int $ignoreId = null): void
    {
        $end = $at->copy()->addMinutes($duration);
        $clash = PtSession::where('trainer_id', $trainer->id)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('scheduled_at', $at->toDateString())
            ->get()
            ->first(fn ($s) => $at->lt($s->endsAt()) && $end->gt($s->scheduled_at));

        if ($clash) {
            throw new InvalidArgumentException("{$trainer->name} already has a session at {$clash->scheduled_at->format('H:i')}.");
        }
    }

    private function assertEditable(PtSession $session): void
    {
        if (! in_array($session->status, ['scheduled', 'in_progress'])) {
            throw new InvalidArgumentException('This session can no longer be changed.');
        }
    }

    private function closeIfFinished(PtSubscription $sub): void
    {
        if ($sub->remainingSessions() === 0) {
            $sub->update(['status' => 'completed']);
        }
    }

    public static function expireSubscriptions(): int
    {
        return PtSubscription::withoutGlobalScopes()->where('status', 'active')
            ->whereDate('expiry_date', '<', today())->update(['status' => 'expired']);
    }
}
