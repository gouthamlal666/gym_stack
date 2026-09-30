<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithUi;
use App\Services\ReminderService;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app'), Title('Gym settings')]
class GymSettings extends Component
{
    use InteractsWithUi, WithFileUploads;

    public string $name = '';
    public ?string $email = '';
    public ?string $phone = '';
    public ?string $address = '';
    public string $primary_color = '#4f46e5';
    public string $currency = 'INR';
    public string $currency_symbol = '₹';
    public string $timezone = 'Asia/Kolkata';
    public string $tax_label = 'GST';
    public $tax_rate = 18;
    public string $invoice_prefix = 'INV';
    public string $member_prefix = 'MEM';
    public ?string $invoice_footer = '';
    public $logo;

    // Reminders & WhatsApp
    public bool $auto_reminders = true;
    public array $reminder_channels = [];
    public string $expiring_days = '7,3,1';
    public string $expired_days = '1,7';
    public string $wa_driver = 'log';
    public ?string $wa_phone_number_id = '';
    public ?string $wa_token = '';
    public bool $wa_has_token = false;
    public ?string $wa_template_expiring = '';
    public ?string $wa_template_expired = '';
    public string $wa_language = 'en';
    public string $wa_country_code = '91';
    public string $wa_test_phone = '';

    public function mount(): void
    {
        $this->requirePermission('gym.settings');
        $gym = $this->gym();
        $this->fill($gym->only('name', 'email', 'phone', 'address', 'primary_color', 'currency', 'currency_symbol', 'timezone', 'tax_label', 'tax_rate', 'invoice_prefix', 'member_prefix'));
        $this->invoice_footer = $gym->setting('invoice_footer');

        $r = ReminderService::settings($gym);
        $this->auto_reminders = (bool) $r['auto'];
        $this->reminder_channels = $r['channels'];
        $this->expiring_days = implode(',', $r['expiring_days']);
        $this->expired_days = implode(',', $r['expired_days']);

        $wa = $gym->setting('whatsapp', []);
        $this->wa_driver = $wa['driver'] ?? 'log';
        $this->wa_phone_number_id = $wa['phone_number_id'] ?? '';
        $this->wa_has_token = ! empty($wa['token']);
        $this->wa_template_expiring = $wa['template_expiring'] ?? '';
        $this->wa_template_expired = $wa['template_expired'] ?? '';
        $this->wa_language = $wa['language'] ?? 'en';
        $this->wa_country_code = $wa['country_code'] ?? '91';
    }

    private function dayList(string $value): array
    {
        return collect(explode(',', $value))->map(fn ($d) => (int) trim($d))->filter(fn ($d) => $d >= 0 && $d <= 60)->unique()->sortDesc()->values()->all();
    }

    public function saveReminders(): void
    {
        $this->validate([
            'reminder_channels.*' => 'in:whatsapp,mail',
            'expiring_days' => ['required', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/'],
            'expired_days' => ['required', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/'],
            'wa_driver' => 'required|in:log,meta',
            'wa_phone_number_id' => 'required_if:wa_driver,meta|nullable|string|max:40',
            'wa_token' => [$this->wa_driver === 'meta' && ! $this->wa_has_token ? 'required' : 'nullable', 'string', 'max:1000'],
            'wa_template_expiring' => 'nullable|string|max:120',
            'wa_template_expired' => 'nullable|string|max:120',
            'wa_language' => 'required|string|max:10',
            'wa_country_code' => 'required|digits_between:1,4',
        ], ['expiring_days.regex' => 'Use comma-separated numbers, e.g. 7,3,1', 'expired_days.regex' => 'Use comma-separated numbers, e.g. 1,7']);

        $gym = $this->gym();
        $settings = $gym->settings ?? [];
        $settings['reminders'] = [
            'auto' => $this->auto_reminders, 'channels' => array_values($this->reminder_channels),
            'expiring_days' => $this->dayList($this->expiring_days), 'expired_days' => $this->dayList($this->expired_days),
        ];
        $existingToken = $settings['whatsapp']['token'] ?? null;
        $settings['whatsapp'] = [
            'driver' => $this->wa_driver, 'phone_number_id' => $this->wa_phone_number_id,
            'token' => $this->wa_token ? Crypt::encryptString($this->wa_token) : $existingToken, // stored encrypted, never shown again
            'template_expiring' => $this->wa_template_expiring, 'template_expired' => $this->wa_template_expired,
            'language' => $this->wa_language, 'country_code' => $this->wa_country_code,
        ];
        $gym->update(['settings' => $settings]);
        $this->wa_has_token = ! empty($settings['whatsapp']['token']);
        $this->wa_token = '';
        $this->toast('Reminder settings saved.');
    }

    public function sendTestWhatsApp(): void
    {
        $this->validate(['wa_test_phone' => 'required|string|max:30']);
        $client = WhatsAppClient::forGym($this->gym()->refresh());
        $to = $client->normalize($this->wa_test_phone);
        if (! $to) {
            $this->addError('wa_test_phone', 'Enter a valid phone number.');

            return;
        }
        try {
            // Meta only allows free text inside a 24h conversation; "hello_world" is the sample template every account has.
            $status = $client->send($to, new WhatsAppMessage("Test message from {$this->gym()->name} ✅", $client->driver() === 'meta' ? 'hello_world' : null, [], 'en_US'));
            $this->toast($status === 'logged' ? 'Test mode: message written to the log.' : "Test message sent to +{$to}.");
        } catch (\Throwable $e) {
            $this->toast('WhatsApp error: '.$e->getMessage(), 'error');
        }
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'currency' => 'required|string|size:3',
            'currency_symbol' => 'required|string|max:5',
            'timezone' => 'required|timezone',
            'tax_label' => 'required|string|max:20',
            'tax_rate' => 'required|numeric|min:0|max:50',
            'invoice_prefix' => 'required|alpha_dash|max:10',
            'member_prefix' => 'required|alpha_dash|max:10',
            'invoice_footer' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:2048',
        ]);
        $gym = $this->gym();
        $update = collect($data)->except('logo', 'invoice_footer')->all();
        if ($this->logo) {
            $update['logo_path'] = $this->logo->store('logos', 'public');
        }
        $update['settings'] = array_merge($gym->settings ?? [], ['invoice_footer' => $this->invoice_footer]);
        $gym->update($update);

        session()->flash('toast', 'Settings saved.');

        return $this->redirectRoute('settings.gym'); // full reload so the new brand colour applies
    }

    public function render()
    {
        return view('livewire.settings.gym-settings');
    }
}
