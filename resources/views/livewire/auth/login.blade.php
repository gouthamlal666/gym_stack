<div>
    <h1 class="text-2xl font-semibold text-slate-900">Welcome back</h1>
    <p class="mt-1 text-sm text-slate-500">Sign in to your gym account.</p>

    @if(session('status'))<div class="mt-6 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
    <form wire:submit="login" class="mt-8 space-y-5">
        <x-field label="Email" name="email">
            <input type="email" wire:model="email" class="input" autocomplete="email" autofocus>
        </x-field>
        <x-field name="password">
            <div class="mb-1 flex items-center justify-between">
                <label class="label mb-0">Password</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm text-brand hover:underline">Forgot password?</a>
            </div>
            <input type="password" wire:model="password" class="input" autocomplete="current-password">
        </x-field>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-brand"> Remember me
        </label>
        <button class="btn-primary w-full py-2.5" wire:loading.attr="disabled">
            <span wire:loading.remove>Sign in</span><span wire:loading>Signing in…</span>
        </button>
    </form>

    <div class="my-6 flex items-center gap-3 text-xs text-slate-400"><div class="h-px flex-1 bg-slate-200"></div>OR<div class="h-px flex-1 bg-slate-200"></div></div>
    <a href="{{ route('login.otp') }}" wire:navigate class="btn-secondary w-full py-2.5"><x-icon name="lock" class="size-4"/> Sign in with a one-time code</a>

    <p class="mt-8 text-center text-sm text-slate-500">New gym owner? <a href="{{ route('register') }}" wire:navigate class="font-medium text-brand hover:underline">Create your gym account</a></p>
</div>
