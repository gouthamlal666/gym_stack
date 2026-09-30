<div class="mx-auto max-w-4xl">
    <x-page-header :title="$member ? 'Edit '.$member->name : 'New member'" :back="$member ? route('members.show', $member) : route('members.index')"/>

    <form wire:submit="save" class="space-y-6">
        <x-card title="Training type" subtitle="Only Personal Training customers get access to the advanced PT modules.">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach(['regular' => ['Regular gym member', 'Gym floor access, classes, attendance and billing.'], 'pt' => ['⭐ Personal training customer', 'Unlocks PT sessions, body tracking, progress photos, assessments, workouts, nutrition and goals.']] as $value => [$label, $desc])
                    <label @class(['flex cursor-pointer gap-3 rounded-xl border p-4 transition', 'border-brand bg-brand-50 ring-1 ring-brand' => $training_type === $value, 'border-slate-200 hover:border-slate-300' => $training_type !== $value])>
                        <input type="radio" wire:model.live="training_type" value="{{ $value }}" class="mt-0.5 text-brand">
                        <div><div class="text-sm font-semibold">{{ $label }}</div><div class="mt-0.5 text-xs text-slate-500">{{ $desc }}</div></div>
                    </label>
                @endforeach
            </div>
        </x-card>

        <x-card title="Personal details">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex items-center gap-4 sm:col-span-2">
                    <x-avatar :name="$first_name.' '.$last_name" :src="$photo ? $photo->temporaryUrl() : $member?->photoUrl()" size="size-16"/>
                    <label class="btn-secondary cursor-pointer"><x-icon name="camera" class="size-4"/> Photo<input type="file" wire:model="photo" class="hidden" accept="image/*"></label>
                    @error('photo')<p class="error">{{ $message }}</p>@enderror
                </div>
                <x-field label="First name *" name="first_name"><input wire:model="first_name" class="input"></x-field>
                <x-field label="Last name" name="last_name"><input wire:model="last_name" class="input"></x-field>
                <x-field label="Phone *" name="phone"><input wire:model="phone" class="input"></x-field>
                <x-field label="Email" name="email"><input type="email" wire:model="email" class="input"></x-field>
                <x-field label="Gender" name="gender">
                    <select wire:model="gender" class="input"><option value="">—</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select>
                </x-field>
                <x-field label="Date of birth" name="date_of_birth"><input type="date" wire:model="date_of_birth" class="input"></x-field>
                <x-field label="Address" name="address" class="sm:col-span-2"><textarea wire:model="address" rows="2" class="input"></textarea></x-field>
            </div>
        </x-card>

        <x-card title="Gym details">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field label="Branch" name="branch_id">
                    <select wire:model="branch_id" class="input"><option value="">—</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
                </x-field>
                <x-field label="Assigned trainer" name="trainer_id">
                    <select wire:model="trainer_id" class="input"><option value="">—</option>@foreach($trainers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                </x-field>
                <x-field label="Joined on" name="joined_on"><input type="date" wire:model="joined_on" class="input"></x-field>
                @if($member)
                    <x-field label="Status" name="status"><select wire:model="status" class="input"><option value="active">Active</option><option value="inactive">Inactive</option></select></x-field>
                @endif
            </div>
        </x-card>

        <x-card title="Emergency contact & health">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field label="Contact name" name="emergency_contact_name"><input wire:model="emergency_contact_name" class="input"></x-field>
                <x-field label="Contact phone" name="emergency_contact_phone"><input wire:model="emergency_contact_phone" class="input"></x-field>
                <x-field label="Relation" name="emergency_contact_relation"><input wire:model="emergency_contact_relation" class="input" placeholder="Spouse, parent…"></x-field>
                <x-field label="Medical notes / injuries" name="medical_notes" class="sm:col-span-3"><textarea wire:model="medical_notes" rows="2" class="input"></textarea></x-field>
            </div>
        </x-card>

        @unless($member)
            <x-card title="Membership" subtitle="Optional — assign a plan now and an invoice is raised automatically.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Plan" name="plan_id">
                        <select wire:model="plan_id" class="input"><option value="">No plan yet</option>@foreach($plans as $p)<option value="{{ $p->id }}">{{ $p->name }} — {{ \App\Support\Money::format($p->price) }}{{ $p->admission_fee > 0 ? ' + '.\App\Support\Money::format($p->admission_fee).' admission' : '' }}</option>@endforeach</select>
                    </x-field>
                    <x-field label="Discount" name="discount"><input type="number" step="0.01" wire:model="discount" class="input"></x-field>
                </div>
            </x-card>
        @endunless

        @if(! $member?->user_id)
            <x-card>
                <label class="flex items-start gap-3">
                    <input type="checkbox" wire:model="create_login" class="mt-1 rounded border-slate-300 text-brand">
                    <div><div class="text-sm font-medium">Create member portal login</div><div class="text-xs text-slate-500">The member gets an email to set their password and can view their plan, bills{{ $training_type === 'pt' ? ', PT sessions and progress' : '' }}.</div></div>
                </label>
            </x-card>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ $member ? route('members.show', $member) : route('members.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            <button class="btn-primary" wire:loading.attr="disabled">{{ $member ? 'Save changes' : 'Create member' }}</button>
        </div>
    </form>
</div>
