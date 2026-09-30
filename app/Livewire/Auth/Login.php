<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\OtpService;
use App\Support\LoginFlow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest'), Title('Sign in')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(OtpService $otp)
    {
        $this->validate();
        $key = Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        $user = User::where('email', $this->email)->first();
        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }
        RateLimiter::clear($key);

        if ($reason = LoginFlow::blockedReason($user)) {
            throw ValidationException::withMessages(['email' => $reason]);
        }

        if ($user->two_factor_enabled) {
            session(['two_factor.user_id' => $user->id, 'two_factor.remember' => $this->remember]);
            $otp->issue($user, 'two_factor');

            return $this->redirectRoute('two-factor');
        }

        return LoginFlow::complete($user, $this->remember);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
