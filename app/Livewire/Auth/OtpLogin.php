<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\OtpService;
use App\Support\LoginFlow;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest'), Title('Sign in with code')]
class OtpLogin extends Component
{
    public string $email = '';
    public string $code = '';
    public bool $sent = false;

    public function send(OtpService $otp): void
    {
        $this->validate(['email' => 'required|email']);
        $key = 'otp-send|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many requests. Please wait a minute.']);
        }
        RateLimiter::hit($key, 60);

        // Always respond the same way so the form cannot be used to discover accounts.
        if ($user = User::where('email', $this->email)->first()) {
            $otp->issue($user, 'login');
        }
        $this->sent = true;
    }

    public function verify(OtpService $otp)
    {
        $this->validate(['code' => 'required|digits:'.config('gym.otp.length')]);
        $user = User::where('email', $this->email)->first();

        if (! $user || ! $otp->verify($user, 'login', $this->code)) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }
        if ($reason = LoginFlow::blockedReason($user)) {
            throw ValidationException::withMessages(['code' => $reason]);
        }

        return LoginFlow::complete($user);
    }

    public function render()
    {
        return view('livewire.auth.otp-login');
    }
}
