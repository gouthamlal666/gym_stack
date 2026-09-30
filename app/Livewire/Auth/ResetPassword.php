<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest'), Title('Choose a new password')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request('email');
    }

    public function resetPassword()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            ['email' => $this->email, 'password' => $this->password, 'password_confirmation' => $this->password_confirmation, 'token' => $this->token],
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', 'Password updated. You can sign in now.');

        return $this->redirectRoute('login');
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
