<?php

namespace App\Livewire\Account;

use App\Livewire\Concerns\InteractsWithUi;
use App\Support\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app'), Title('Profile & security')]
class Profile extends Component
{
    use InteractsWithUi, WithFileUploads;

    public string $name = '';
    public string $email = '';
    public ?string $phone = '';
    public $avatar;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $two_factor_enabled = false;

    public function mount(): void
    {
        $u = Auth::user();
        $this->fill(['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'two_factor_enabled' => $u->two_factor_enabled]);
    }

    public function saveProfile(): void
    {
        $user = Auth::user();
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'avatar' => 'nullable|image|max:2048',
        ]);
        unset($data['avatar']);
        if ($this->avatar) {
            $data['avatar_path'] = $this->avatar->store('avatars', 'public');
        }
        $user->update($data);
        $this->avatar = null;
        $this->toast('Profile updated.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        Auth::user()->update(['password' => Hash::make($this->password)]);
        Activity::log('password', 'Changed password', Auth::user());
        $this->reset('current_password', 'password', 'password_confirmation');
        $this->toast('Password changed.');
    }

    public function updatedTwoFactorEnabled(bool $value): void
    {
        Auth::user()->update(['two_factor_enabled' => $value]);
        $this->toast($value ? 'Two-factor authentication enabled. You will get an email code on sign-in.' : 'Two-factor authentication disabled.');
    }

    public function render()
    {
        return view('livewire.account.profile');
    }
}
