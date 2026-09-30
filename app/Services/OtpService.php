<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function issue(User $user, string $purpose): void
    {
        OtpCode::where('user_id', $user->id)->where('purpose', $purpose)->whereNull('consumed_at')->delete();

        $length = config('gym.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        OtpCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('gym.otp.ttl_minutes')),
        ]);

        $user->notify(new OtpCodeNotification($code, $purpose));
    }

    public function verify(User $user, string $purpose, string $code): bool
    {
        $otp = OtpCode::where('user_id', $user->id)->where('purpose', $purpose)
            ->whereNull('consumed_at')->latest('id')->first();

        if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= config('gym.otp.max_attempts')) {
            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}
