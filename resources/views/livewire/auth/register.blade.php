<div>
    <h1 class="text-2xl font-semibold text-slate-900">Create your gym account</h1>
    <p class="mt-1 text-sm text-slate-500">14-day free trial. No card required.</p>
    <form wire:submit="register" class="mt-8 space-y-4">
        <x-field label="Gym name" name="gym_name"><input wire:model="gym_name" class="input" autofocus placeholder="e.g. Iron Temple Fitness"></x-field>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Your name" name="name"><input wire:model="name" class="input"></x-field>
            <x-field label="Phone" name="phone"><input wire:model="phone" class="input"></x-field>
        </div>
        <x-field label="Email" name="email"><input type="email" wire:model="email" class="input"></x-field>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Password" name="password"><input type="password" wire:model="password" class="input"></x-field>
            <x-field label="Confirm password" name="password_confirmation"><input type="password" wire:model="password_confirmation" class="input"></x-field>
        </div>
        <button class="btn-primary w-full py-2.5" wire:loading.attr="disabled">Create gym</button>
    </form>
    <p class="mt-8 text-center text-sm text-slate-500">Already have an account? <a href="{{ route('login') }}" wire:navigate class="font-medium text-brand hover:underline">Sign in</a></p>
</div>
