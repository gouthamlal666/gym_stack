<div>
    <h1 class="text-2xl font-semibold text-slate-900">Sign in with a code</h1>
    <p class="mt-1 text-sm text-slate-500">We'll email you a {{ config('gym.otp.length') }}-digit one-time password.</p>

    @if(! $sent)
        <form wire:submit="send" class="mt-8 space-y-5">
            <x-field label="Email" name="email"><input type="email" wire:model="email" class="input" autofocus></x-field>
            <button class="btn-primary w-full py-2.5" wire:loading.attr="disabled">Send code</button>
        </form>
    @else
        <div class="mt-6 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">If an account exists for <b>{{ $email }}</b>, a code is on its way.</div>
        <form wire:submit="verify" class="mt-6 space-y-5">
            <x-field label="Verification code" name="code">
                <input type="text" inputmode="numeric" maxlength="{{ config('gym.otp.length') }}" wire:model="code" class="input text-center text-2xl tracking-[0.5em]" autofocus autocomplete="one-time-code">
            </x-field>
            <button class="btn-primary w-full py-2.5">Verify & sign in</button>
            <button type="button" wire:click="send" class="btn-ghost w-full">Resend code</button>
        </form>
    @endif
    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" wire:navigate class="text-brand hover:underline">Back to password sign-in</a></p>
</div>
