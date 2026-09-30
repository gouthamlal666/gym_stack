<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginFlow
{
    public static function complete(User $user, bool $remember = false)
    {
        Auth::login($user, $remember);
        session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        Activity::log('login', 'Signed in', null, null, $user);

        return redirect()->intended($user->homeRoute());
    }

    public static function blockedReason(User $user): ?string
    {
        if (! $user->is_active) {
            return 'Your account is disabled.';
        }
        if ($user->gym && ! $user->gym->isActive()) {
            return 'This gym account is suspended. Contact support.';
        }

        return null;
    }
}
