<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Support\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MembershipService
{
    public function __construct(private BillingService $billing) {}

    /** Creates a membership + its invoice. Returns the invoice so staff can collect payment. */
    public function subscribe(Member $member, MembershipPlan $plan, string $startDate, float $discount = 0,
        bool $chargeAdmission = false, string $changeType = 'new', ?Membership $previous = null, float $credit = 0): Invoice
    {
        return DB::transaction(function () use ($member, $plan, $startDate, $discount, $chargeAdmission, $changeType, $previous, $credit) {
            $start = Carbon::parse($startDate)->startOfDay();

            $membership = Membership::create([
                'gym_id' => $member->gym_id,
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'previous_membership_id' => $previous?->id,
                'change_type' => $changeType,
                'start_date' => $start,
                'end_date' => $start->copy()->addDays($plan->duration_days - 1),
                'price' => $plan->price,
                'discount' => $discount + $credit,
                'status' => $start->isFuture() ? 'upcoming' : 'active',
            ]);

            $items = [['description' => "{$plan->name} membership ({$membership->start_date->format('d M Y')} – {$membership->end_date->format('d M Y')})", 'unit_price' => (float) $plan->price]];
            if ($chargeAdmission && $plan->admission_fee > 0) {
                $items[] = ['description' => 'Admission fee', 'unit_price' => (float) $plan->admission_fee];
            }
            $notes = $credit > 0 ? 'Includes credit of '.number_format($credit, 2).' from previous plan.' : null;

            return $this->billing->createInvoice($member, $items, $discount + $credit, $membership, notes: $notes);
        });
    }

    public function renew(Membership $current, ?MembershipPlan $plan = null, float $discount = 0): Invoice
    {
        $plan ??= $current->plan;
        $start = $current->end_date->isPast() ? today() : $current->end_date->copy()->addDay();

        return $this->subscribe($current->member, $plan, $start->toDateString(), $discount, false, 'renewal', $current);
    }

    /** Upgrade/downgrade immediately; unused days of the current plan are credited on the new invoice. */
    public function changePlan(Membership $current, MembershipPlan $newPlan, float $discount = 0): Invoice
    {
        if (! in_array($current->status, ['active', 'frozen'])) {
            throw new InvalidArgumentException('Only an active membership can be changed.');
        }

        $netPaidPerDay = ((float) $current->price - (float) $current->discount) / max(1, $current->plan->duration_days);
        $credit = round($netPaidPerDay * $current->daysLeft(), 2);
        $credit = min($credit, (float) $newPlan->price - $discount);
        $type = (float) $newPlan->price >= (float) $current->plan->price ? 'upgrade' : 'downgrade';

        return DB::transaction(function () use ($current, $newPlan, $discount, $credit, $type) {
            $current->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => ucfirst($type).' to '.$newPlan->name]);

            return $this->subscribe($current->member, $newPlan, today()->toDateString(), $discount, false, $type, $current, max(0, $credit));
        });
    }

    public function freeze(Membership $m, string $from, int $days): void
    {
        $allowed = $m->plan->max_freeze_days - $m->frozen_days_used;
        if ($m->status !== 'active') {
            throw new InvalidArgumentException('Only an active membership can be frozen.');
        }
        if ($days < 1 || $days > $allowed) {
            throw new InvalidArgumentException("This plan allows {$allowed} more freeze day(s).");
        }

        $fromDate = Carbon::parse($from);
        $m->update([
            'status' => 'frozen',
            'frozen_from' => $fromDate,
            'frozen_until' => $fromDate->copy()->addDays($days - 1),
            'frozen_days_used' => $m->frozen_days_used + $days,
            'end_date' => $m->end_date->copy()->addDays($days),
        ]);
        Activity::log('freeze', "Membership frozen for {$days} day(s)", $m);
    }

    public function unfreeze(Membership $m): void
    {
        if ($m->status !== 'frozen') {
            return;
        }
        // Give back unused freeze days if unfrozen early.
        $unused = $m->frozen_until->isFuture() ? (int) today()->diffInDays($m->frozen_until) + 1 : 0;
        $m->update([
            'status' => 'active',
            'end_date' => $m->end_date->copy()->subDays($unused),
            'frozen_days_used' => max(0, $m->frozen_days_used - $unused),
            'frozen_until' => $unused ? today()->subDay() : $m->frozen_until,
        ]);
        Activity::log('unfreeze', 'Membership unfrozen', $m);
    }

    public function extend(Membership $m, int $days, string $reason): void
    {
        $m->update([
            'end_date' => $m->end_date->copy()->addDays($days),
            'extended_days' => $m->extended_days + $days,
            'status' => $m->status === 'expired' && $m->end_date->copy()->addDays($days)->gte(today()) ? 'active' : $m->status,
        ]);
        Activity::log('extend', "Membership extended by {$days} day(s): {$reason}", $m);
    }

    public function cancel(Membership $m, string $reason): void
    {
        $m->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => $reason]);
    }

    /** Nightly housekeeping: activate upcoming, end freezes, expire lapsed memberships. */
    public static function refreshStatuses(): array
    {
        $today = today();
        $activated = Membership::withoutGlobalScopes()->where('status', 'upcoming')->whereDate('start_date', '<=', $today)->update(['status' => 'active']);
        $unfrozen = Membership::withoutGlobalScopes()->where('status', 'frozen')->whereDate('frozen_until', '<', $today)->update(['status' => 'active']);
        $expired = Membership::withoutGlobalScopes()->where('status', 'active')->whereDate('end_date', '<', $today)->update(['status' => 'expired']);

        return compact('activated', 'unfrozen', 'expired');
    }
}
