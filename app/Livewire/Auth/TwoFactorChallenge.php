<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\OtpService;
use App\Support\LoginFlow;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest'), Title('Two-factor verification')]
class TwoFactorChallenge extends Component
{
    public string $code = '';

    public function mount()
    {
        if (! session('two_factor.user_id')) {
            return $this->redirectRoute('login');
        }
    }

    public function verify(OtpService $otp)
    {
        $this->validate(['code' => 'required|digits:'.config('gym.otp.length')]);
        $user = User::find(session('two_factor.user_id'));

        if (! $user || ! $otp->verify($user, 'two_factor', $this->code)) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }
        $remember = (bool) session('two_factor.remember');
        session()->forget(['two_factor.user_id', 'two_factor.remember']);

        return LoginFlow::complete($user, $remember);
    }

    public function resend(OtpService $otp): void
    {
        if ($user = User::find(session('two_factor.user_id'))) {
            $otp->issue($user, 'two_factor');
        }
        session()->flash('resent', true);
    }

    public function render()
    {
        return view('livewire.auth.two-factor');
    }
}
