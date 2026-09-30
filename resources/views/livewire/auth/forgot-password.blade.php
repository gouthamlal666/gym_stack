<div>
    <h1 class="text-2xl font-semibold text-slate-900">Reset your password</h1>
    <p class="mt-1 text-sm text-slate-500">Enter your email and we'll send you a reset link.</p>
    @if($sent)
        <div class="mt-6 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">If an account exists for <b>{{ $email }}</b>, a reset link has been sent.</div>
    @else
        <form wire:submit="send" class="mt-8 space-y-5">
            <x-field label="Email" name="email"><input type="email" wire:model="email" class="input" autofocus></x-field>
            <button class="btn-primary w-full py-2.5">Email reset link</button>
        </form>
    @endif
    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" wire:navigate class="text-brand hover:underline">Back to sign in</a></p>
</div>
