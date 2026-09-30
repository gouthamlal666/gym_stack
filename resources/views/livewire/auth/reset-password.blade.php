<div>
    <h1 class="text-2xl font-semibold text-slate-900">Choose a new password</h1>
    <form wire:submit="resetPassword" class="mt-8 space-y-5">
        <x-field label="Email" name="email"><input type="email" wire:model="email" class="input"></x-field>
        <x-field label="New password" name="password"><input type="password" wire:model="password" class="input" autofocus></x-field>
        <x-field label="Confirm password" name="password_confirmation"><input type="password" wire:model="password_confirmation" class="input"></x-field>
        <button class="btn-primary w-full py-2.5">Reset password</button>
    </form>
</div>
