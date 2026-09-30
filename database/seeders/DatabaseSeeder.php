<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BodyAssessment;
use App\Models\Branch;
use App\Models\Exercise;
use App\Models\Expense;
use App\Models\FitnessAssessment;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\NutritionPlan;
use App\Models\ProgressPhoto;
use App\Models\PtGoal;
use App\Models\PtPackage;
use App\Models\PtSession;
use App\Models\PtWorkoutPlan;
use App\Models\Trainer;
use App\Models\User;
use App\Services\BillingService;
use App\Services\MembershipService;
use App\Services\PtService;
use App\Support\Activity;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    private BillingService $billing;
    private MembershipService $memberships;
    private PtService $pt;

    public function run(): void
    {
        Activity::$disabled = true;
        mt_srand(42);
        fake()->seed(42);
        $this->billing = app(BillingService::class);
        $this->memberships = app(MembershipService::class);
        $this->pt = app(PtService::class);

        User::create(['name' => 'Platform Admin', 'email' => 'admin@gymstack.test', 'role' => 'super_admin', 'password' => 'password']);
        $this->exerciseLibrary();

        // ── Tenant: Iron Temple Fitness ──────────────────────────────────────
        $gym = Gym::create([
            'name' => 'Iron Temple Fitness', 'slug' => 'iron-temple', 'email' => 'hello@irontemple.test', 'phone' => '+91 98470 12345',
            'address' => "MG Road, Kochi\nKerala 682016", 'primary_color' => '#4f46e5', 'subscription_plan' => 'pro',
            'settings' => ['invoice_footer' => "GSTIN: 32ABCDE1234F1Z5\nMemberships are non-transferable. Thank you for training with us!"],
        ])->refresh();
        $central = Branch::create(['gym_id' => $gym->id, 'name' => 'Kochi Central', 'code' => 'KCH', 'phone' => '+91 98470 12345', 'address' => 'MG Road, Kochi', 'opens_at' => '05:00', 'closes_at' => '22:00']);
        $kakkanad = Branch::create(['gym_id' => $gym->id, 'name' => 'Kakkanad', 'code' => 'KKD', 'phone' => '+91 98470 67890', 'address' => 'Infopark Road, Kakkanad', 'opens_at' => '05:30', 'closes_at' => '21:30']);

        $admin = User::create(['gym_id' => $gym->id, 'name' => 'Arjun Nair', 'email' => 'owner@irontemple.test', 'role' => 'gym_admin', 'password' => 'password', 'phone' => '+91 90000 00001']);
        User::create(['gym_id' => $gym->id, 'branch_id' => $central->id, 'name' => 'Meera Menon', 'email' => 'manager@irontemple.test', 'role' => 'manager', 'password' => 'password']);
        User::create(['gym_id' => $gym->id, 'branch_id' => $central->id, 'name' => 'Anu Thomas', 'email' => 'reception@irontemple.test', 'role' => 'receptionist', 'password' => 'password']);
        User::create(['gym_id' => $gym->id, 'name' => 'Vivek Iyer', 'email' => 'accounts@irontemple.test', 'role' => 'accountant', 'password' => 'password']);

        Auth::setUser($admin); // stamps gym_id on everything below via the tenancy trait

        $john = $this->trainer('John Mathew', 'john@irontemple.test', $central, ['Fat loss', 'Strength', 'Body recomposition'], ['ACE CPT', 'K11 Sports Nutrition'], 8, 20, ['06:00', '14:00']);
        $priya = $this->trainer('Priya Raghavan', 'priya@irontemple.test', $central, ['Mobility', 'Yoga', 'Women\'s fitness'], ['ACSM EP-C'], 5, 15, ['07:00', '13:00'], ['16:00', '20:00']);
        $sam = $this->trainer('Samuel Joseph', 'samuel@irontemple.test', $kakkanad, ['Powerlifting', 'Muscle gain'], ['NSCA CSCS'], 10, 25, ['15:00', '21:00']);

        $plans = collect([
            ['name' => 'Monthly', 'billing_cycle' => 'monthly', 'duration_days' => 30, 'price' => 1500, 'admission_fee' => 500, 'max_freeze_days' => 0],
            ['name' => 'Quarterly', 'billing_cycle' => 'quarterly', 'duration_days' => 90, 'price' => 4000, 'admission_fee' => 500, 'max_freeze_days' => 10],
            ['name' => 'Half-yearly', 'billing_cycle' => 'half_yearly', 'duration_days' => 180, 'price' => 7500, 'admission_fee' => 0, 'max_freeze_days' => 20],
            ['name' => 'Yearly', 'billing_cycle' => 'yearly', 'duration_days' => 365, 'price' => 13000, 'admission_fee' => 0, 'max_freeze_days' => 30, 'description' => 'Best value — includes 2 free PT intro sessions'],
            ['name' => '7-day Trial', 'billing_cycle' => 'custom', 'duration_days' => 7, 'price' => 0, 'admission_fee' => 0, 'is_trial' => true],
        ])->map(fn ($p) => MembershipPlan::create($p));

        $packages = collect([
            ['name' => 'PT Starter', 'sessions_count' => 8, 'price' => 8000, 'validity_days' => 30, 'description' => '2 sessions a week for a month'],
            ['name' => 'Transformation 20', 'sessions_count' => 20, 'price' => 18000, 'validity_days' => 61, 'description' => 'Our most popular fat-loss & recomposition program'],
            ['name' => 'Elite 36', 'sessions_count' => 36, 'price' => 30000, 'validity_days' => 90, 'description' => '3x/week for 12 weeks + nutrition coaching'],
        ])->map(fn ($p) => PtPackage::create($p));

        // ── Regular members ──────────────────────────────────────────────────
        $firstNames = ['Rahul', 'Sneha', 'Aditya', 'Fathima', 'Kiran', 'Divya', 'Nikhil', 'Aparna', 'Rohan', 'Lakshmi', 'Vishnu', 'Neha', 'Joel', 'Anjali', 'Faisal', 'Gayathri', 'Harish', 'Irene', 'Jithin', 'Kavya', 'Manu', 'Nisha', 'Pranav', 'Riya', 'Sreejith', 'Teena', 'Unni', 'Varsha', 'Akhil', 'Bincy', 'Cyril', 'Deepa'];
        $lastNames = ['Varma', 'Pillai', 'Kurian', 'Rahman', 'Menon', 'Nair', 'George', 'Krishnan', 'Das', 'Paul'];
        $members = collect();
        foreach ($firstNames as $i => $first) {
            $joined = today()->subDays(mt_rand(10, 300));
            $member = Member::create([
                'branch_id' => $i % 3 === 0 ? $kakkanad->id : $central->id, 'member_code' => Member::nextCode($gym),
                'first_name' => $first, 'last_name' => $lastNames[$i % 10], 'email' => strtolower($first).'.'.strtolower($lastNames[$i % 10]).'@example.test',
                'phone' => '+91 9'.mt_rand(100000000, 999999999), 'gender' => $i % 2 ? 'female' : 'male',
                'date_of_birth' => today()->subYears(mt_rand(19, 52))->subDays(mt_rand(0, 360)), 'joined_on' => $joined,
                'emergency_contact_name' => fake()->name(), 'emergency_contact_phone' => '+91 9'.mt_rand(100000000, 999999999), 'emergency_contact_relation' => ['Spouse', 'Parent', 'Sibling'][$i % 3],
                'address' => 'Kochi, Kerala',
            ]);
            $plan = $plans[[0, 0, 1, 1, 1, 2, 3, 0][$i % 8]];
            $this->membershipWithPayment($member, $plan, $joined, $i);
            $members->push($member);
        }
        $rahul = $members->first();
        $rahul->update(['user_id' => User::create(['gym_id' => $gym->id, 'branch_id' => $central->id, 'name' => $rahul->name, 'email' => 'rahul@member.test', 'role' => 'member', 'password' => 'password'])->id]);

        // ── ⭐ Alex — the flagship PT customer from the spec ─────────────────
        $alex = Member::create([
            'branch_id' => $central->id, 'member_code' => Member::nextCode($gym), 'first_name' => 'Alex', 'last_name' => 'Fernandez',
            'email' => 'alex@member.test', 'phone' => '+91 98950 11223', 'gender' => 'male', 'date_of_birth' => '1994-03-12',
            'joined_on' => today()->subDays(95), 'training_type' => 'regular', 'trainer_id' => $john->id,
            'emergency_contact_name' => 'Maria Fernandez', 'emergency_contact_phone' => '+91 98950 44556', 'emergency_contact_relation' => 'Spouse',
            'medical_notes' => 'Old left-knee ligament strain — avoid deep loaded lunges.', 'address' => 'Panampilly Nagar, Kochi',
        ]);
        $alex->update(['user_id' => User::create(['gym_id' => $gym->id, 'branch_id' => $central->id, 'name' => 'Alex Fernandez', 'email' => 'alex@member.test', 'role' => 'member', 'password' => 'password'])->id]);
        $this->membershipWithPayment($alex, $plans[3], today()->subDays(95), 0, true);

        $start1 = today()->subDays(91);
        $this->enrollWithPayment($alex, $packages[0], $john, $start1, 8, $start1, 8);        // first package, all done
        $start2 = today()->subDays(29);                                                     // "01 Sep"
        $sub = $this->enrollWithPayment($alex, $packages[1], $john, $start2, 12, $start2, 2); // 12 completed, 8 remaining
        PtSession::create(['pt_subscription_id' => $sub->id, 'member_id' => $alex->id, 'trainer_id' => $john->id, 'scheduled_at' => today()->setTime(10, 0), 'duration_minutes' => 60, 'focus' => 'Chest + Cardio']);
        foreach ([2 => 'Back + Core', 4 => 'Shoulder + Arms', 7 => 'Legs'] as $days => $focus) {
            PtSession::create(['pt_subscription_id' => $sub->id, 'member_id' => $alex->id, 'trainer_id' => $john->id, 'scheduled_at' => today()->addDays($days)->setTime(10, 0), 'duration_minutes' => 60, 'focus' => $focus]);
        }

        $journey = [ // days-ago => [weight, bf, muscle, chest, waist, hip, biceps, thigh, shoulder, neck, forearm, calf]
            91 => [82.0, 24.0, 31.0, 102, 94, 99, 35.0, 58, 48, 39, 29, 37],
            76 => [80.6, 23.0, 31.2, 101.5, 92.5, 98.5, 35.3, 57.5, 48.2, 38.8, 29.1, 37],
            61 => [79.3, 21.8, 31.5, 101, 91, 98, 35.8, 57, 48.5, 38.5, 29.3, 37.2],
            45 => [78.2, 20.6, 31.9, 100.5, 89.5, 97, 36.2, 56.5, 48.8, 38.3, 29.5, 37.3],
            29 => [77.3, 19.6, 32.2, 100, 88, 96.5, 36.5, 56, 49, 38, 29.6, 37.5],
            15 => [76.6, 18.8, 32.5, 99.5, 87, 96, 36.8, 55.8, 49.2, 37.9, 29.8, 37.6],
            1 => [76.0, 18.0, 32.8, 99, 86, 95.5, 37.0, 55.5, 49.5, 37.8, 30.0, 37.8],
        ];
        $assessments = [];
        foreach ($journey as $ago => [$w, $bf, $mm, $ch, $wa, $hp, $bi, $th, $sh, $nk, $fa, $ca]) {
            $assessments[$ago] = BodyAssessment::create([
                'member_id' => $alex->id, 'trainer_id' => $john->id, 'assessed_on' => today()->subDays($ago), 'is_initial' => $ago === 91,
                'weight' => $w, 'height' => 172, 'body_fat' => $bf, 'muscle_mass' => $mm, 'chest' => $ch, 'waist' => $wa, 'hip' => $hp,
                'biceps' => $bi, 'thigh' => $th, 'shoulder' => $sh, 'neck' => $nk, 'forearm' => $fa, 'calf' => $ca,
                'next_assessment_on' => $ago === 1 ? today()->addDays(5) : null,
                'notes' => $ago === 91 ? 'Initial assessment. Sedentary desk job, wants to lose 8 kg before his wedding anniversary.' : null,
            ]);
        }
        foreach ([91, 45, 1] as $ago) {
            $this->progressPhotos($alex, $assessments[$ago], $admin);
        }

        PtGoal::create(['member_id' => $alex->id, 'trainer_id' => $john->id, 'goal_type' => 'weight_loss', 'title' => 'Lose 8 KG', 'metric' => 'weight',
            'start_value' => 82, 'target_value' => 74, 'unit' => 'kg', 'start_date' => today()->subDays(91), 'target_date' => today()->addDays(40)]);
        PtGoal::create(['member_id' => $alex->id, 'trainer_id' => $john->id, 'goal_type' => 'recomposition', 'title' => 'Waist under 84 cm', 'metric' => 'waist',
            'start_value' => 94, 'target_value' => 84, 'unit' => 'cm', 'start_date' => today()->subDays(91), 'target_date' => today()->addDays(60)]);

        foreach ([[91, [4, 3, 4, 3, 4, 5, 4, 4], ['pushups' => 14, 'squats' => 28, 'plank' => 45, 'run_1km' => 7.4, 'vo2max' => 34, 'grip' => 38, 'sit_reach' => 12]],
                  [29, [6, 5, 5, 6, 6, 6, 6, 6], ['pushups' => 24, 'squats' => 38, 'plank' => 95, 'run_1km' => 6.1, 'vo2max' => 39, 'grip' => 42, 'sit_reach' => 18]]] as [$ago, $r, $tests]) {
            $fa = FitnessAssessment::create(array_combine(array_keys(config('gym.fitness_ratings')), $r) + [
                'member_id' => $alex->id, 'trainer_id' => $john->id, 'assessed_on' => today()->subDays($ago),
                'notes' => $ago === 29 ? 'Big jump in core endurance. Hip mobility still limiting squat depth.' : 'Rounded shoulders, weak glutes. Focus on posture & posterior chain.',
            ]);
            foreach ($tests as $k => $v) {
                $fa->results()->create(['test_key' => $k, 'value' => $v]);
            }
        }

        $this->workoutPlan($alex, $john);
        $this->nutritionPlan($alex, $john);
        $alex->update(['training_type' => 'pt']);

        // ── More PT customers ────────────────────────────────────────────────
        foreach ([[$members[1], $priya, $packages[0], 'Mobility & strength', 'muscle_gain', 'muscle_mass', 23.5, 26], [$members[4], $john, $packages[1], 'Drop to 20% body fat', 'weight_loss', 'body_fat', 27, 20], [$members[6], $sam, $packages[2], 'Gain 5 kg lean mass', 'muscle_gain', 'weight', 68, 73]] as [$m, $t, $pkg, $title, $type, $metric, $from, $to]) {
            $s = today()->subDays(mt_rand(10, 25));
            $done = mt_rand(3, 6);
            $this->enrollWithPayment($m, $pkg, $t, $s, $done, $s, mt_rand(2, 3), (int) substr($t->availability[1]['start'], 0, 2));
            PtSession::create(['pt_subscription_id' => $m->activePtSubscription->id, 'member_id' => $m->id, 'trainer_id' => $t->id,
                'scheduled_at' => today()->addDays(mt_rand(1, 3))->setTime((int) substr($t->availability[1]['start'], 0, 2) + 1, 0), 'focus' => 'Full body']);
            $w = $metric === 'weight' ? $from : mt_rand(60, 85);
            BodyAssessment::create(['member_id' => $m->id, 'trainer_id' => $t->id, 'assessed_on' => $s, 'is_initial' => true, 'weight' => $w, 'height' => mt_rand(155, 182),
                'body_fat' => $metric === 'body_fat' ? $from : mt_rand(18, 28), 'muscle_mass' => $metric === 'muscle_mass' ? $from : mt_rand(24, 34), 'waist' => mt_rand(78, 96), 'chest' => mt_rand(88, 104)]);
            BodyAssessment::create(['member_id' => $m->id, 'trainer_id' => $t->id, 'assessed_on' => today()->subDays(2), 'weight' => $metric === 'weight' ? $from + 1.5 : $w - 1.2, 'height' => mt_rand(155, 182),
                'body_fat' => $metric === 'body_fat' ? $from - 2.5 : mt_rand(17, 26), 'muscle_mass' => $metric === 'muscle_mass' ? $from + 0.8 : mt_rand(24, 34), 'waist' => mt_rand(76, 94), 'next_assessment_on' => today()->addDays(12)]);
            PtGoal::create(['member_id' => $m->id, 'trainer_id' => $t->id, 'goal_type' => $type, 'title' => $title, 'metric' => $metric, 'start_value' => $from, 'target_value' => $to,
                'unit' => config("gym.body_metrics.$metric.unit"), 'start_date' => $s, 'target_date' => $s->copy()->addWeeks(12)]);
        }

        // ── Attendance (last 60 days) ────────────────────────────────────────
        foreach ($members->push($alex) as $m) {
            for ($d = 60; $d >= 0; $d--) {
                if (mt_rand(0, 100) < ($m->is($alex) ? 70 : 45)) {
                    $in = today()->subDays($d)->setTime([6, 7, 7, 8, 17, 18, 18, 19][mt_rand(0, 7)], mt_rand(0, 59));
                    if ($in->isFuture()) {
                        continue;
                    }
                    Attendance::create(['member_id' => $m->id, 'branch_id' => $m->branch_id, 'check_in_at' => $in, 'check_out_at' => $d === 0 && mt_rand(0, 1) ? null : $in->copy()->addMinutes(mt_rand(45, 100))]);
                }
            }
        }

        // ── Expenses (last 6 months) ─────────────────────────────────────────
        for ($mo = 5; $mo >= 0; $mo--) {
            $base = now()->subMonths($mo)->startOfMonth();
            foreach ([[$central, 'rent', 9000], [$kakkanad, 'rent', 6500], [null, 'salary', 14000], [$central, 'utilities', mt_rand(2200, 3200)], [$kakkanad, 'utilities', mt_rand(1500, 2200)],
                      [$central, 'maintenance', mt_rand(500, 1500)], [null, 'marketing', mt_rand(1000, 2500)]] as [$b, $cat, $amt]) {
                $date = $base->copy()->addDays(mt_rand(0, 25));
                if ($date->isFuture()) {
                    continue;
                }
                Expense::create(['branch_id' => $b?->id, 'category' => $cat, 'amount' => $amt, 'expense_date' => $date, 'recorded_by' => $admin->id,
                    'vendor' => ['rent' => 'Landlord', 'salary' => 'Payroll', 'utilities' => 'KSEB / Water Authority', 'maintenance' => 'FitFix Services', 'marketing' => 'Meta Ads'][$cat]]);
            }
        }
        Expense::create(['branch_id' => $kakkanad->id, 'category' => 'equipment', 'amount' => 22000, 'expense_date' => now()->subMonths(3)->startOfMonth()->addDays(9), 'vendor' => 'Life Fitness India', 'description' => 'Adjustable benches + dumbbell rack', 'recorded_by' => $admin->id]);

        MembershipService::refreshStatuses();
        PtService::expireSubscriptions();

        // ── A second tenant to prove isolation ───────────────────────────────
        Auth::forgetUser();
        $other = Gym::create(['name' => 'Pulse CrossFit', 'slug' => 'pulse-crossfit', 'primary_color' => '#dc2626', 'subscription_plan' => 'starter']);
        $ob = Branch::create(['gym_id' => $other->id, 'name' => 'Main branch']);
        User::create(['gym_id' => $other->id, 'name' => 'Pulse Owner', 'email' => 'owner@pulse.test', 'role' => 'gym_admin', 'password' => 'password']);
        Member::create(['gym_id' => $other->id, 'branch_id' => $ob->id, 'member_code' => 'MEM-01001', 'first_name' => 'Other', 'last_name' => 'Gym Member', 'phone' => '+91 90000 99999', 'joined_on' => today()]);

        Activity::$disabled = false;
    }

    private function trainer(string $name, string $email, Branch $branch, array $spec, array $certs, int $years, float $commission, array ...$shifts): Trainer
    {
        $user = User::create(['gym_id' => $branch->gym_id, 'branch_id' => $branch->id, 'name' => $name, 'email' => $email, 'role' => 'trainer', 'password' => 'password']);
        $availability = [];
        foreach (range(1, 6) as $d) {
            $availability[$d] = ['start' => $shifts[0][0], 'end' => end($shifts)[1]];
        }

        return Trainer::create(['user_id' => $user->id, 'branch_id' => $branch->id, 'specializations' => $spec, 'certifications' => $certs, 'experience_years' => $years,
            'commission_rate' => $commission, 'availability' => $availability, 'bio' => "$name has $years years of coaching experience."]);
    }

    private function membershipWithPayment(Member $member, MembershipPlan $plan, Carbon $start, int $i, bool $paid = false): void
    {
        $invoice = $this->memberships->subscribe($member, $plan, $start->toDateString(), $i % 5 === 0 ? 200 : 0, true);
        $invoice->update(['issue_date' => $start, 'due_date' => $start->copy()->addDays(7)]);
        $mode = $paid ? 'full' : ['full', 'full', 'full', 'partial', 'none', 'full'][$i % 6];
        if ($mode !== 'none' && $invoice->total > 0) {
            $amount = $mode === 'full' ? (float) $invoice->total : round((float) $invoice->total / 2);
            $this->billing->recordPayment($invoice, $amount, ['cash', 'upi', 'card', 'upi'][$i % 4], null, null, $start->copy()->setTime(mt_rand(7, 20), mt_rand(0, 59)));
        }
        // Renew lapsed monthly memberships for regulars so the data has renewal history.
        $membership = $invoice->billable;
        if ($membership->end_date->isPast() && $i % 4 !== 3) {
            $renewal = $this->memberships->renew($membership->refresh());
            $renewStart = $membership->end_date->copy()->addDay();
            $renewal->update(['issue_date' => $renewStart, 'due_date' => $renewStart->copy()->addDays(7)]);
            $this->billing->recordPayment($renewal, (float) $renewal->total, 'upi', null, null, $renewStart->copy()->setTime(9, 0));
            $next = $renewal->billable;
            if ($next->end_date->isPast()) {
                $this->memberships->renew($next->refresh());
            }
        }
    }

    private function enrollWithPayment(Member $member, PtPackage $pkg, Trainer $trainer, Carbon $start, int $completed, Carbon $from, int $everyDays, int $hour = 10)
    {
        $invoice = $this->pt->enroll($member, $pkg, $trainer, $start->toDateString());
        $invoice->update(['issue_date' => $start, 'due_date' => $start->copy()->addDays(3)]);
        $this->billing->recordPayment($invoice, (float) $invoice->total, 'card', 'POS-'.mt_rand(10000, 99999), null, $start->copy()->setTime(11, 0));
        $sub = $invoice->billable;
        $focuses = ['Chest + Cardio', 'Back + Core', 'Legs', 'Shoulder + Arms', 'Full Body + Cardio'];
        $moves = [['Barbell Bench Press', 4, '10', 50], ['Incline Dumbbell Press', 3, '12', 18], ['Lat Pulldown', 4, '12', 45], ['Goblet Squat', 4, '12', 20], ['Romanian Deadlift', 3, '10', 50], ['Plank', 3, '60s', null], ['Treadmill Intervals', 1, '15 min', null]];
        for ($n = 0; $n < $completed; $n++) {
            $at = $from->copy()->addDays($n * $everyDays)->setTime($hour, 0);
            if ($at->isFuture()) {
                break;
            }
            $s = PtSession::create(['pt_subscription_id' => $sub->id, 'member_id' => $member->id, 'trainer_id' => $trainer->id, 'scheduled_at' => $at, 'duration_minutes' => 60,
                'focus' => $focuses[$n % 5], 'status' => $n === 2 && $pkg->sessions_count === 36 ? 'no_show' : 'completed',
                'started_at' => $at, 'ended_at' => $at->copy()->addMinutes(mt_rand(52, 65)), 'calories' => mt_rand(380, 620), 'rating' => mt_rand(3, 5),
                'trainer_notes' => 'Good tempo control. Increase load next session.', 'trainer_feedback' => ['Great energy today 💪', 'Solid session — keep hydrating.', 'Form improving on hinges. Proud of you!'][$n % 3]]);
            if ($s->status === 'completed') {
                foreach (array_slice($moves, $n % 3, 4) as $k => [$name, $sets, $reps, $wt]) {
                    $s->exercises()->create(['name' => $name, 'sets' => $sets, 'reps' => $reps, 'weight' => $wt ? $wt + $n : null, 'sort' => $k, 'exercise_id' => Exercise::where('name', $name)->value('id')]);
                }
            }
        }
        if ($sub->remainingSessions() === 0) {
            $sub->update(['status' => 'completed']);
        }

        return $sub;
    }

    private function workoutPlan(Member $member, Trainer $trainer): void
    {
        $plan = PtWorkoutPlan::create(['member_id' => $member->id, 'trainer_id' => $trainer->id, 'title' => '12-week Fat Loss Program', 'goal' => 'Fat Loss', 'level' => 'intermediate',
            'duration_weeks' => 12, 'start_date' => today()->subDays(29), 'notes' => 'Progressive overload on compound lifts; 8–10k steps daily; protect the left knee.']);
        $days = [
            1 => ['Chest + Cardio', [['Barbell Bench Press', 4, '8-10', '55 kg', 90, '3-1-1', 8, 'Retract scapula, feet planted'], ['Incline Dumbbell Press', 3, '10-12', '20 kg', 75, '2-0-1', 8], ['Cable Crossover', 3, '15', '12 kg', 60, '2-1-1', 7], ['Push-up', 3, 'AMRAP', 'BW', 60, null, 9], ['Treadmill Intervals', 1, '20 min', null, 0, null, 7, '1 min fast / 1 min walk']]],
            2 => ['Back + Core', [['Lat Pulldown', 4, '10-12', '50 kg', 75, '2-1-1', 8], ['Seated Cable Row', 3, '12', '45 kg', 75, '2-1-1', 8], ['Dumbbell Row', 3, '10 each', '22 kg', 60, null, 8], ['Plank', 3, '60s', 'BW', 45, null, 7], ['Dead Bug', 3, '12 each', 'BW', 45, null, 6]]],
            3 => ['Legs', [['Goblet Squat', 4, '12', '24 kg', 90, '3-0-1', 8, 'Box squat to parallel — knee friendly'], ['Romanian Deadlift', 4, '10', '60 kg', 90, '3-1-1', 8], ['Leg Press', 3, '12', '120 kg', 90, null, 8], ['Walking Lunge', 2, '10 each', 'BW', 60, null, 7, 'Short stride, skip if knee flares'], ['Standing Calf Raise', 3, '15', '40 kg', 45, null, 8]]],
            4 => ['Rest / Mobility', [], true],
            5 => ['Shoulder + Arms', [['Overhead Press', 4, '8', '30 kg', 90, '2-1-1', 8], ['Lateral Raise', 3, '15', '8 kg', 45, null, 8], ['Barbell Curl', 3, '12', '25 kg', 60, null, 8], ['Tricep Pushdown', 3, '12', '25 kg', 60, null, 8]]],
            6 => ['Full Body + Cardio', [['Kettlebell Swing', 4, '20', '16 kg', 60, null, 8], ['Burpee', 3, '12', 'BW', 60, null, 9], ['Farmer Carry', 3, '40 m', '24 kg each', 60, null, 7], ['Rowing Machine', 1, '15 min', null, 0, null, 7]]],
            7 => ['Rest', [], true],
        ];
        foreach ($days as $dow => $def) {
            $day = $plan->days()->create(['day_of_week' => $dow, 'focus' => $def[0], 'is_rest' => $def[2] ?? false]);
            foreach ($def[1] as $i => $e) {
                $day->exercises()->create(['name' => $e[0], 'sets' => $e[1], 'reps' => $e[2], 'weight' => $e[3], 'rest_seconds' => $e[4], 'tempo' => $e[5], 'rpe' => $e[6],
                    'instructions' => $e[7] ?? null, 'sort' => $i, 'exercise_id' => Exercise::where('name', $e[0])->value('id'),
                    'video_url' => Exercise::where('name', $e[0])->value('video_url')]);
            }
        }
    }

    private function nutritionPlan(Member $member, Trainer $trainer): void
    {
        $plan = NutritionPlan::create(['member_id' => $member->id, 'trainer_id' => $trainer->id, 'title' => 'Fat loss — 500 kcal deficit', 'daily_calories' => 2100,
            'protein_g' => 160, 'carbs_g' => 200, 'fat_g' => 70, 'water_liters' => 3.5, 'start_date' => today()->subDays(29),
            'notes' => "One cheat meal on Sunday. Creatine 5 g daily. Keep sodium moderate the day before assessments."]);
        foreach ([
            ['breakfast', '07:30', "Oats 60 g with milk\n2 whole eggs + 3 whites\n1 banana", 560, 38, 70, 14],
            ['morning_snack', '10:30', "Greek yogurt 150 g\nHandful of almonds", 250, 18, 12, 14],
            ['lunch', '13:30', "Brown rice 1 cup\nGrilled chicken 150 g\nSambar & cucumber salad", 620, 48, 70, 14],
            ['pre_workout', '17:00', "Black coffee\n2 rice cakes with peanut butter", 180, 5, 22, 8],
            ['post_workout', '19:00', "Whey protein 1 scoop\n1 apple", 220, 26, 28, 2],
            ['dinner', '21:00', "2 chapati\nFish curry (seer fish 150 g)\nStir-fried vegetables", 480, 32, 45, 18],
        ] as $i => [$type, $time, $items, $kcal, $p, $c, $f]) {
            $plan->meals()->create(['meal_type' => $type, 'time' => $time, 'items' => $items, 'calories' => $kcal, 'protein_g' => $p, 'carbs_g' => $c, 'fat_g' => $f, 'sort' => $i]);
        }
    }

    /** Generates simple placeholder silhouettes so the before/after feature has something to show. */
    private function progressPhotos(Member $member, BodyAssessment $a, User $by): void
    {
        foreach (array_keys(config('gym.photo_angles')) as $angle) {
            $img = imagecreatetruecolor(300, 400);
            imagefill($img, 0, 0, imagecolorallocate($img, 226, 232, 240));
            $body = imagecolorallocate($img, 100, 116, 139);
            $girth = (int) (40 + ($a->body_fat - 15) * 4.5); // wider silhouette at higher body fat
            $side = in_array($angle, ['left', 'right']);
            imagefilledellipse($img, 150, 70, 60, 70, $body);
            imagefilledellipse($img, 150, 200, ($side ? 0.6 : 1) * ($girth + 50), 190, $body);
            imagefilledrectangle($img, 150 - ($side ? 18 : 40), 280, 150 + ($side ? 18 : 40), 380, $body);
            $white = imagecolorallocate($img, 255, 255, 255);
            $dark = imagecolorallocate($img, 30, 41, 59);
            imagestring($img, 5, 10, 10, strtoupper($angle), $dark);
            imagestring($img, 3, 10, 30, $a->assessed_on->format('d M Y').'  '.$a->weight.'kg  '.$a->body_fat.'%', $dark);
            imagestring($img, 2, 90, 385, 'demo placeholder', $white);
            ob_start();
            imagejpeg($img, null, 85);
            $path = "progress/{$member->gym_id}/{$member->id}/demo-{$a->id}-{$angle}.jpg";
            Storage::disk('local')->put($path, ob_get_clean());
            imagedestroy($img);
            ProgressPhoto::create(['member_id' => $member->id, 'body_assessment_id' => $a->id, 'uploaded_by' => $by->id, 'angle' => $angle, 'path' => $path, 'taken_on' => $a->assessed_on]);
        }
    }

    private function exerciseLibrary(): void
    {
        $yt = fn ($q) => 'https://www.youtube.com/results?search_query='.urlencode($q.' form');
        foreach ([
            ['Barbell Bench Press', 'Chest', 'Barbell'], ['Incline Dumbbell Press', 'Chest', 'Dumbbell'], ['Cable Crossover', 'Chest', 'Cable'], ['Push-up', 'Chest', 'Bodyweight'],
            ['Lat Pulldown', 'Back', 'Machine'], ['Seated Cable Row', 'Back', 'Cable'], ['Dumbbell Row', 'Back', 'Dumbbell'], ['Pull-up', 'Back', 'Bodyweight'], ['Deadlift', 'Back', 'Barbell'],
            ['Goblet Squat', 'Legs', 'Kettlebell'], ['Back Squat', 'Legs', 'Barbell'], ['Romanian Deadlift', 'Legs', 'Barbell'], ['Leg Press', 'Legs', 'Machine'], ['Walking Lunge', 'Legs', 'Bodyweight'], ['Standing Calf Raise', 'Legs', 'Machine'], ['Hip Thrust', 'Glutes', 'Barbell'],
            ['Overhead Press', 'Shoulders', 'Barbell'], ['Lateral Raise', 'Shoulders', 'Dumbbell'], ['Face Pull', 'Shoulders', 'Cable'],
            ['Barbell Curl', 'Arms', 'Barbell'], ['Hammer Curl', 'Arms', 'Dumbbell'], ['Tricep Pushdown', 'Arms', 'Cable'], ['Skull Crusher', 'Arms', 'EZ bar'],
            ['Plank', 'Core', 'Bodyweight'], ['Dead Bug', 'Core', 'Bodyweight'], ['Hanging Leg Raise', 'Core', 'Bodyweight'], ['Russian Twist', 'Core', 'Plate'],
            ['Kettlebell Swing', 'Full body', 'Kettlebell'], ['Burpee', 'Full body', 'Bodyweight'], ['Farmer Carry', 'Full body', 'Dumbbell'],
            ['Treadmill Intervals', 'Cardio', 'Treadmill'], ['Rowing Machine', 'Cardio', 'Rower'], ['Assault Bike', 'Cardio', 'Air bike'], ['Stair Climber', 'Cardio', 'Machine'],
        ] as [$name, $muscle, $equipment]) {
            Exercise::create(['gym_id' => null, 'name' => $name, 'muscle_group' => $muscle, 'equipment' => $equipment, 'video_url' => $yt($name)]);
        }
    }
}
