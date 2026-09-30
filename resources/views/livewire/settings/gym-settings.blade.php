<div class="mx-auto max-w-4xl">
    <x-page-header title="Gym settings" subtitle="Branding, billing and regional preferences."/>
    <form wire:submit="save" class="space-y-6">
        <x-card title="Branding" subtitle="White-label your members' and staff experience.">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex items-center gap-4 sm:col-span-2">
                    @if($logo)<img src="{{ $logo->temporaryUrl() }}" class="size-16 rounded-xl object-cover">
                    @elseif(auth()->user()->gym->logoUrl())<img src="{{ auth()->user()->gym->logoUrl() }}" class="size-16 rounded-xl object-cover">
                    @else<div class="flex size-16 items-center justify-center rounded-xl bg-brand text-white"><x-icon name="bolt" class="size-8"/></div>@endif
                    <label class="btn-secondary cursor-pointer">Upload logo<input type="file" wire:model="logo" class="hidden" accept="image/*"></label>
                    @error('logo')<p class="error">{{ $message }}</p>@enderror
                </div>
                <x-field label="Gym name" name="name"><input wire:model="name" class="input"></x-field>
                <x-field label="Brand colour" name="primary_color">
                    <div class="flex gap-2"><input type="color" wire:model.live="primary_color" class="h-10 w-14 rounded border border-slate-300"><input wire:model.live="primary_color" class="input font-mono"></div>
                </x-field>
                <x-field label="Email" name="email"><input wire:model="email" class="input"></x-field>
                <x-field label="Phone" name="phone"><input wire:model="phone" class="input"></x-field>
                <x-field label="Address" name="address" class="sm:col-span-2"><textarea wire:model="address" rows="2" class="input"></textarea></x-field>
            </div>
        </x-card>
        <x-card title="Billing & tax">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field label="Currency code" name="currency"><input wire:model="currency" class="input uppercase"></x-field>
                <x-field label="Currency symbol" name="currency_symbol"><input wire:model="currency_symbol" class="input"></x-field>
                <x-field label="Timezone" name="timezone"><input wire:model="timezone" class="input" list="tz"></x-field>
                <datalist id="tz">@foreach(['Asia/Kolkata', 'Asia/Dubai', 'Europe/London', 'America/New_York', 'Asia/Singapore', 'Australia/Sydney'] as $tz)<option value="{{ $tz }}">@endforeach</datalist>
                <x-field label="Tax label" name="tax_label"><input wire:model="tax_label" class="input"></x-field>
                <x-field label="Tax rate %" name="tax_rate"><input type="number" step="0.01" wire:model="tax_rate" class="input"></x-field>
                <div></div>
                <x-field label="Invoice prefix" name="invoice_prefix"><input wire:model="invoice_prefix" class="input"></x-field>
                <x-field label="Member ID prefix" name="member_prefix"><input wire:model="member_prefix" class="input"></x-field>
                <x-field label="Invoice footer" name="invoice_footer" class="sm:col-span-3"><textarea wire:model="invoice_footer" rows="2" class="input" placeholder="GSTIN, bank details, terms…"></textarea></x-field>
            </div>
        </x-card>
        <div class="flex justify-end"><button class="btn-primary">Save settings</button></div>
    </form>
    <div class="mt-6 space-y-6">
        <x-card title="Membership reminders" subtitle="Automatic renewal reminders for expiring and expired (not renewed) members.">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex items-center gap-3 sm:col-span-2">
                    <input type="checkbox" wire:model="auto_reminders" class="size-5 rounded text-brand">
                    <span class="text-sm"><b>Send automatically</b> every day at 09:30</span>
                </label>
                <x-field label="Channels" name="reminder_channels">
                    <div class="flex gap-4 pt-1 text-sm">
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="reminder_channels" value="whatsapp" class="rounded text-brand"> WhatsApp</label>
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="reminder_channels" value="mail" class="rounded text-brand"> Email</label>
                    </div>
                </x-field>
                <div></div>
                <x-field label="Days before expiry" name="expiring_days" hint="e.g. 7,3,1 — a reminder on each of these days"><input wire:model="expiring_days" class="input"></x-field>
                <x-field label="Days after expiry (win-back)" name="expired_days" hint="Only members who have not renewed"><input wire:model="expired_days" class="input"></x-field>
            </div>
        </x-card>

        <x-card title="WhatsApp Business" subtitle="Connect your WhatsApp Business number (Meta WhatsApp Cloud API).">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Mode" name="wa_driver" class="sm:col-span-2">
                    <select wire:model.live="wa_driver" class="input">
                        <option value="log">Test mode — write messages to the log</option>
                        <option value="meta">Live — WhatsApp Cloud API (Meta)</option>
                    </select>
                </x-field>
                @if($wa_driver === 'meta')
                    <x-field label="Phone number ID" name="wa_phone_number_id" hint="Meta App → WhatsApp → API Setup"><input wire:model="wa_phone_number_id" class="input font-mono"></x-field>
                    <x-field label="Access token" name="wa_token" :hint="$wa_has_token ? 'A token is saved (encrypted). Leave blank to keep it.' : 'Use a permanent System User token.'"><input type="password" wire:model="wa_token" class="input font-mono" placeholder="{{ $wa_has_token ? '••••••••••••' : 'EAAG…' }}" autocomplete="off"></x-field>
                    <x-field label="Template: expiring" name="wa_template_expiring" hint="Approved template name"><input wire:model="wa_template_expiring" class="input font-mono" placeholder="membership_expiring"></x-field>
                    <x-field label="Template: expired" name="wa_template_expired"><input wire:model="wa_template_expired" class="input font-mono" placeholder="membership_expired"></x-field>
                    <x-field label="Template language" name="wa_language"><input wire:model="wa_language" class="input" placeholder="en"></x-field>
                    <p class="text-xs text-slate-500 sm:col-span-2">WhatsApp only allows business-started messages through <b>approved templates</b>. Create them in WhatsApp Manager with 4 body variables: <code>@{{1}}</code> name, <code>@{{2}}</code> plan, <code>@{{3}}</code> end date, <code>@{{4}}</code> gym name. Without a template, messages are sent as plain text, which Meta only delivers inside a 24-hour conversation window.</p>
                @endif
                <x-field label="Default country code" name="wa_country_code" hint="Added to numbers saved without one"><input wire:model="wa_country_code" class="input" placeholder="91"></x-field>
            </div>
            <div class="mt-4 flex flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-4">
                <div class="flex items-end gap-2">
                    <x-field label="Send a test message to" name="wa_test_phone"><input wire:model="wa_test_phone" class="input" placeholder="+91 98470 12345"></x-field>
                    <button type="button" wire:click="sendTestWhatsApp" class="btn-secondary">Send test</button>
                </div>
                <button type="button" wire:click="saveReminders" class="btn-primary">Save reminder settings</button>
            </div>
            <p class="mt-2 text-xs text-slate-500">Save before testing.</p>
        </x-card>
    </div>
</div>
