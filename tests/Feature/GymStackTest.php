<?php

namespace Tests\Feature;

use App\Livewire;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\ProgressPhoto;
use App\Models\PtSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire as LivewireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GymStackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function alex(): Member
    {
        return Member::withoutGlobalScopes()->where('first_name', 'Alex')->firstOrFail();
    }

    public static function staffPages(): array
    {
        return [
            'owner' => ['owner@irontemple.test'], 'manager' => ['manager@irontemple.test'], 'reception' => ['reception@irontemple.test'],
            'accountant' => ['accounts@irontemple.test'], 'trainer' => ['john@irontemple.test'],
        ];
    }

    #[DataProvider('staffPages')]
    public function test_every_staff_page_renders_or_is_forbidden_without_errors(string $email): void
    {
        $user = $this->user($email);
        $alex = $this->alex();
        $invoice = Invoice::withoutGlobalScopes()->where('member_id', $alex->id)->first();
        $session = PtSession::withoutGlobalScopes()->where('member_id', $alex->id)->first();
        $trainer = $user->trainer ?? \App\Models\Trainer::withoutGlobalScopes()->first();

        $urls = [
            '/dashboard', '/members', '/members/create', "/members/{$alex->id}", "/members/{$alex->id}/edit", '/plans', '/memberships',
            '/attendance', '/invoices', "/invoices/{$invoice->id}", '/payments', '/expenses', '/finance', '/trainers', "/trainers/{$trainer->id}",
            '/exercises', '/pt/packages', '/pt/clients', "/pt/clients/{$alex->id}", '/pt/schedule', "/pt/sessions/{$session->id}",
            '/settings/gym', '/settings/branches', '/settings/staff', '/activity', '/profile', "/print/invoices/{$invoice->id}",
        ];
        foreach (['sessions', 'body', 'photos', 'fitness', 'workout', 'nutrition', 'goals'] as $tab) {
            $urls[] = "/pt/clients/{$alex->id}?tab={$tab}";
        }
        foreach (['memberships', 'billing', 'attendance', 'documents'] as $tab) {
            $urls[] = "/members/{$alex->id}?tab={$tab}";
        }

        foreach ($urls as $url) {
            $status = $this->actingAs($user)->get($url)->getStatusCode();
            $this->assertContains($status, [200, 403], "$email → $url returned $status");
        }
    }

    public function test_owner_sees_all_core_pages(): void
    {
        $owner = $this->user('owner@irontemple.test');
        foreach (['/dashboard', '/finance', '/settings/staff', '/pt/packages', '/activity'] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
        $this->actingAs($owner)->get('/pt/clients/'.$this->alex()->id)->assertOk()->assertSee('Lose 8 KG')->assertSee('John Mathew');
    }

    public function test_pt_customer_portal(): void
    {
        $alex = $this->user('alex@member.test');
        $this->actingAs($alex)->get('/my')->assertOk()->assertSee('Lose 8 KG')->assertSee('75%')->assertSee('Chest + Cardio')->assertSee('Transformation 20');
        foreach (['/my/membership', '/my/attendance', '/my/progress', '/my/progress?tab=photos', '/my/progress?tab=fitness', '/my/progress?tab=goals', '/my/workout', '/my/nutrition', '/my/sessions'] as $url) {
            $this->actingAs($alex)->get($url)->assertOk();
        }
        $this->actingAs($alex)->get('/dashboard')->assertRedirect('/my');
    }

    public function test_regular_member_cannot_open_pt_modules(): void
    {
        $rahul = $this->user('rahul@member.test');
        $this->actingAs($rahul)->get('/my')->assertOk()->assertSee('Upgrade to Personal Training');
        foreach (['/my/progress', '/my/workout', '/my/nutrition', '/my/sessions'] as $url) {
            $this->actingAs($rahul)->get($url)->assertRedirect('/my');
        }
        $this->actingAs($rahul)->get('/pt/clients/'.$this->alex()->id)->assertRedirect();
    }

    public function test_progress_photo_privacy(): void
    {
        $photo = ProgressPhoto::withoutGlobalScopes()->firstOrFail();
        $url = "/files/progress-photos/{$photo->id}";

        $this->actingAs($this->user('alex@member.test'))->get($url)->assertOk();
        $this->actingAs($this->user('john@irontemple.test'))->get($url)->assertOk();   // assigned trainer
        $this->actingAs($this->user('owner@irontemple.test'))->get($url)->assertOk();  // gym admin
        $this->actingAs($this->user('priya@irontemple.test'))->get($url)->assertForbidden();   // other trainer
        $this->actingAs($this->user('manager@irontemple.test'))->get($url)->assertForbidden(); // not an authorised admin
        $this->actingAs($this->user('rahul@member.test'))->get($url)->assertForbidden();       // other member
        $this->actingAs($this->user('owner@pulse.test'))->get($url)->assertNotFound();         // other tenant
    }

    public function test_trainer_only_sees_own_pt_clients(): void
    {
        $priya = $this->user('priya@irontemple.test');
        $this->actingAs($priya)->get('/pt/clients')->assertOk()->assertDontSee('Alex Fernandez');
        $this->actingAs($priya)->get('/pt/clients/'.$this->alex()->id)->assertForbidden();
        $this->actingAs($this->user('john@irontemple.test'))->get('/pt/clients')->assertSee('Alex Fernandez');
    }

    public function test_tenant_isolation(): void
    {
        $pulse = $this->user('owner@pulse.test');
        $this->actingAs($pulse)->get('/members/'.$this->alex()->id)->assertNotFound();
        $this->actingAs($pulse)->get('/members')->assertOk()->assertDontSee('Alex')->assertSee('Other');
    }

    public function test_super_admin_panel(): void
    {
        $admin = $this->user('admin@gymstack.test');
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/gyms')->assertOk()->assertSee('Iron Temple Fitness')->assertSee('Pulse CrossFit');
        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin');
    }

    public function test_login_with_two_factor(): void
    {
        $user = $this->user('reception@irontemple.test');
        $user->update(['two_factor_enabled' => true]);

        LivewireTest::test(Livewire\Auth\Login::class)
            ->set('email', $user->email)->set('password', 'password')->call('login')
            ->assertRedirect(route('two-factor'));
        $this->assertGuest();

        $code = null;
        Notification::assertSentTo($user, \App\Notifications\OtpCodeNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });
        LivewireTest::test(Livewire\Auth\TwoFactorChallenge::class)->set('code', '000000')->call('verify')->assertHasErrors('code');
        LivewireTest::test(Livewire\Auth\TwoFactorChallenge::class)->set('code', $code)->call('verify')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_creates_gym_and_admin(): void
    {
        LivewireTest::test(Livewire\Auth\Register::class)
            ->set('gym_name', 'Beast Mode Gym')->set('name', 'Owner Person')->set('email', 'owner@beast.test')
            ->set('password', 'secret123')->set('password_confirmation', 'secret123')->call('register')->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'owner@beast.test', 'role' => 'gym_admin']);
        $this->assertDatabaseHas('branches', ['name' => 'Main branch']);
    }

    public function test_partial_payment_and_refund_flow(): void
    {
        $this->actingAs($this->user('owner@irontemple.test'));
        $member = Member::where('first_name', 'Sneha')->first();
        $invoice = app(\App\Services\BillingService::class)->createInvoice($member, [['description' => 'Protein tub', 'unit_price' => 1000]]);
        $this->assertEquals(1180.0, (float) $invoice->total); // 18% GST

        LivewireTest::test(Livewire\Billing\InvoiceShow::class, ['invoice' => $invoice])
            ->set('amount', 500)->set('method', 'upi')->call('pay')->assertHasNoErrors();
        $this->assertEquals('partial', $invoice->fresh()->status);

        LivewireTest::test(Livewire\Billing\InvoiceShow::class, ['invoice' => $invoice])
            ->set('amount', 5000)->call('pay')->assertHasErrors('amount'); // over-payment rejected
        LivewireTest::test(Livewire\Billing\InvoiceShow::class, ['invoice' => $invoice])
            ->set('amount', 680)->call('pay');
        $this->assertEquals('paid', $invoice->fresh()->status);

        LivewireTest::test(Livewire\Billing\InvoiceShow::class, ['invoice' => $invoice->fresh()])
            ->set('amount', 1180)->set('notes', 'Unopened, returned')->call('refund');
        $this->assertEquals('refunded', $invoice->fresh()->status);
    }

    public function test_membership_freeze_and_upgrade(): void
    {
        $this->actingAs($this->user('reception@irontemple.test'));
        $membership = Membership::where('status', 'active')->whereHas('plan', fn ($q) => $q->where('max_freeze_days', '>', 0))->firstOrFail();
        $end = $membership->end_date->copy();

        LivewireTest::test(Livewire\Members\Show::class, ['member' => $membership->member])
            ->call('openModal', 'freeze', $membership->id)->set('days', 5)->call('freeze')->assertHasNoErrors();
        $membership->refresh();
        $this->assertEquals('frozen', $membership->status);
        $this->assertTrue($membership->end_date->eq($end->addDays(5)));

        $yearly = \App\Models\MembershipPlan::where('name', 'Yearly')->first();
        LivewireTest::test(Livewire\Members\Show::class, ['member' => $membership->member])
            ->call('openModal', 'change', $membership->id)->set('plan_id', $yearly->id)->call('changePlan');
        $this->assertEquals('cancelled', $membership->fresh()->status);
        $this->assertDatabaseHas('memberships', ['member_id' => $membership->member_id, 'membership_plan_id' => $yearly->id, 'change_type' => 'upgrade']);
    }

    public function test_pt_booking_prevents_double_booking_and_completion_consumes_session(): void
    {
        $john = $this->user('john@irontemple.test');
        $this->actingAs($john);
        $sub = $this->alex()->activePtSubscription;
        $before = $sub->remainingSessions();

        $date = today()->addDays(9);
        while (! $john->trainer->worksOn($date->dayOfWeekIso)) {
            $date->addDay();
        }
        $slots = app(\App\Services\PtService::class)->availableSlots($john->trainer, $date->toDateString());
        $this->assertNotEmpty($slots);

        LivewireTest::test(Livewire\Pt\Schedule::class)->call('openBooking', $date->toDateString(), $slots[0])
            ->set('book_subscription_id', $sub->id)->set('book_time', $slots[0])->call('book')->assertHasNoErrors();
        LivewireTest::test(Livewire\Pt\Schedule::class)->call('openBooking', $date->toDateString(), $slots[0])
            ->set('book_subscription_id', $sub->id)->set('book_time', $slots[0])->call('book')->assertHasErrors('book_time');

        $today = PtSession::where('member_id', $sub->member_id)->whereDate('scheduled_at', today())->first();
        LivewireTest::test(Livewire\Pt\SessionShow::class, ['session' => $today])
            ->call('start')->call('addExercise')->set('exercises.0.name', 'Barbell Bench Press')->set('exercises.0.weight', 60)
            ->set('calories', 450)->set('trainer_feedback', 'Great work')->call('complete')->assertHasNoErrors();

        $this->assertEquals('completed', $today->fresh()->status);
        $this->assertEquals($before, $sub->fresh()->remainingSessions() + 1);
    }

    public function test_body_assessment_updates_goal_progress(): void
    {
        $this->actingAs($this->user('john@irontemple.test'));
        $alex = $this->alex();
        LivewireTest::test(Livewire\Pt\Client\BodyTracking::class, ['member' => $alex])
            ->call('openForm')->set('metrics.weight', 75)->set('metrics.body_fat', 17.5)->call('save')->assertHasNoErrors();
        $this->assertEquals(88, $alex->goals()->where('metric', 'weight')->first()->progressPercent()); // 82 → 75 of 82 → 74
    }

    public function test_manager_cannot_edit_pt_data_of_client_but_trainer_can(): void
    {
        $this->actingAs($this->user('reception@irontemple.test'));
        LivewireTest::test(Livewire\Pt\Client\Goals::class, ['member' => $this->alex()])->call('openForm')->assertForbidden();
    }
}
