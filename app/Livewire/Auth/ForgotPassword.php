<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest'), Title('Forgot password')]
class ForgotPassword extends Component
{
    public string $email = '';
    public bool $sent = false;

    public function send(): void
    {
        $this->validate(['email' => 'required|email']);
        Password::sendResetLink(['email' => $this->email]);
        $this->sent = true; // same response whether or not the account exists
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
