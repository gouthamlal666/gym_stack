<div class="mx-auto max-w-3xl space-y-6">
    <x-card title="Profile" subtitle="Your personal information">
        <form wire:submit="saveProfile" class="space-y-4">
            <div class="flex items-center gap-4">
                <x-avatar :name="$name" :src="$avatar ? $avatar->temporaryUrl() : auth()->user()->avatarUrl()" size="size-16"/>
                <label class="btn-secondary cursor-pointer">Change photo <input type="file" wire:model="avatar" class="hidden" accept="image/*"></label>
                @error('avatar')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Full name" name="name"><input wire:model="name" class="input"></x-field>
                <x-field label="Phone" name="phone"><input wire:model="phone" class="input"></x-field>
                <x-field label="Email" name="email" class="sm:col-span-2"><input type="email" wire:model="email" class="input"></x-field>
            </div>
            <div class="flex justify-end"><button class="btn-primary">Save profile</button></div>
        </form>
    </x-card>

    <x-card title="Password">
        <form wire:submit="updatePassword" class="grid gap-4 sm:grid-cols-3">
            <x-field label="Current password" name="current_password"><input type="password" wire:model="current_password" class="input"></x-field>
            <x-field label="New password" name="password"><input type="password" wire:model="password" class="input"></x-field>
            <x-field label="Confirm" name="password_confirmation"><input type="password" wire:model="password_confirmation" class="input"></x-field>
            <div class="flex justify-end sm:col-span-3"><button class="btn-primary">Update password</button></div>
        </form>
    </x-card>

    <x-card title="Two-factor authentication" subtitle="Require a one-time code emailed to you each time you sign in.">
        <label class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg {{ $two_factor_enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }}"><x-icon name="shield"/></div>
                <div>
                    <div class="text-sm font-medium">{{ $two_factor_enabled ? 'Enabled' : 'Disabled' }}</div>
                    <div class="text-xs text-slate-500">Codes are sent to {{ auth()->user()->email }}</div>
                </div>
            </div>
            <input type="checkbox" wire:model.live="two_factor_enabled" class="size-5 rounded border-slate-300 text-brand">
        </label>
    </x-card>
</div>
