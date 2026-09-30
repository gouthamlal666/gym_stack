<div>
    <div class="flex size-12 items-center justify-center rounded-xl bg-brand-50 text-brand"><x-icon name="shield" class="size-6"/></div>
    <h1 class="mt-4 text-2xl font-semibold text-slate-900">Two-factor verification</h1>
    <p class="mt-1 text-sm text-slate-500">Enter the code we just emailed you to finish signing in.</p>
    @if(session('resent'))<div class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">A new code has been sent.</div>@endif
    <form wire:submit="verify" class="mt-6 space-y-5">
        <x-field label="Verification code" name="code">
            <input type="text" inputmode="numeric" maxlength="{{ config('gym.otp.length') }}" wire:model="code" class="input text-center text-2xl tracking-[0.5em]" autofocus autocomplete="one-time-code">
        </x-field>
        <button class="btn-primary w-full py-2.5">Verify</button>
        <button type="button" wire:click="resend" class="btn-ghost w-full">Resend code</button>
    </form>
</div>
