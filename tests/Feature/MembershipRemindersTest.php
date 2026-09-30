<?php

namespace Tests\Feature;

use App\Livewire\Memberships\Index;
use App\Models\Gym;
use App\Models\Membership;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\MembershipReminder;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed();
        $this->actingAs(User::where('email', 'reception@irontemple.test')->first());
    }

    private function useMetaWhatsApp(array $extra = []): void
    {
        $gym = Gym::where('slug', 'iron-temple')->first();
        $gym->update(['settings' => array_merge($gym->settings ?? [], ['whatsapp' => $extra + [
            'driver' => 'meta', 'phone_number_id' => '123456', 'token' => Crypt::encryptString('secret-token'),
            'template_expiring' => 'membership_expiring', 'template_expired' => 'membership_expired', 'language' => 'en', 'country_code' => '91',
        ]])]);
    }

    public function test_expired_tab_only_lists_members_who_have_not_renewed(): void
    {
        $lapsed = Membership::lapsed()->with('member')->get();
        $this->assertNotEmpty($lapsed);
        foreach ($lapsed as $m) {
            $this->assertFalse($m->member->memberships()->whereIn('status', ['active', 'frozen', 'upcoming'])->exists(), "{$m->member->name} has renewed");
        }
        $this->assertLessThan(Membership::where('status', 'expired')->count(), $lapsed->count());
        $this->assertEquals($lapsed->count(), $lapsed->unique('member_id')->count());
    }

    public function test_bulk_reminder_sends_whatsapp_template_and_email_and_logs_them(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        $this->useMetaWhatsApp();

        $expiring = Membership::where('status', 'active')->whereDate('end_date', '<=', today()->addDays(7))->with('member')->first();
        $this->assertNotNull($expiring);

        Livewire::test(Index::class, ['filter' => 'expiring'])
            ->set('filter', 'expiring')
            ->call('selectAll')
            ->call('openReminder')
            ->set('channels', ['whatsapp', 'mail'])
            ->call('sendReminders')
            ->assertHasNoErrors();

        Http::assertSent(function ($request) use ($expiring) {
            return str_contains($request->url(), '/123456/messages')
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request['type'] === 'template'
                && $request['template']['name'] === 'membership_expiring'
                && $request['template']['components'][0]['parameters'][0]['text'] === $expiring->member->first_name
                && str_starts_with($request['to'], '91');
        });
        $this->assertDatabaseHas('notification_logs', ['membership_id' => $expiring->id, 'channel' => 'whatsapp', 'status' => 'sent', 'type' => 'membership_expiring']);
        $this->assertDatabaseHas('notification_logs', ['membership_id' => $expiring->id, 'channel' => 'mail', 'status' => 'sent']);
    }

    public function test_whatsapp_api_failure_is_logged_not_thrown(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template name does not exist']], 400)]);
        $this->useMetaWhatsApp();
        $m = Membership::lapsed()->first();

        $logs = app(ReminderService::class)->send($m, ['whatsapp']);

        $this->assertEquals('failed', $logs['whatsapp']->status);
        $this->assertStringContainsString('Template name does not exist', $logs['whatsapp']->error);
    }

    public function test_test_mode_logs_whatsapp_without_calling_api(): void
    {
        Http::fake();
        $m = Membership::lapsed()->first();
        $logs = app(ReminderService::class)->send($m, ['whatsapp']);
        $this->assertEquals('logged', $logs['whatsapp']->status);
        Http::assertNothingSent();
    }

    public function test_missing_email_is_skipped(): void
    {
        $m = Membership::lapsed()->with('member')->first();
        $m->member->update(['email' => null]);
        $this->assertEquals('skipped', app(ReminderService::class)->send($m->fresh(), ['mail'])['mail']->status);
    }

    public function test_automatic_run_targets_threshold_days_once_per_day(): void
    {
        Notification::fake();
        auth()->logout();
        $m = Membership::withoutGlobalScopes()->where('status', 'active')->first();
        $m->update(['end_date' => today()->addDays(3)]);

        $first = app(ReminderService::class)->runAutomatic();
        $second = app(ReminderService::class)->runAutomatic();

        $this->assertTrue(NotificationLog::withoutGlobalScopes()->where('membership_id', $m->id)->whereNull('sent_by')->exists());
        $this->assertGreaterThan(0, $first['memberships']);
        $this->assertEquals(0, $second['memberships']); // no duplicates the same day
    }

    public function test_reminder_text_for_expired_and_expiring(): void
    {
        $m = Membership::lapsed()->with('member.gym', 'plan')->first();
        $this->assertStringContainsString('expired on', (new MembershipReminder($m, 'expired'))->text());
        $m->end_date = today()->addDay();
        $this->assertStringContainsString('expires tomorrow', (new MembershipReminder($m, 'expiring'))->text());
    }

    public function test_accountant_cannot_send_reminders(): void
    {
        $this->actingAs(User::where('email', 'accounts@irontemple.test')->first());
        Livewire::test(Index::class)->set('filter', 'expired')->call('openReminder', Membership::lapsed()->value('id'))->assertForbidden();
    }
}
