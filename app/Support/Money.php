<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class Money
{
    public static function format($amount, ?string $symbol = null): string
    {
        $symbol ??= Auth::user()?->gym?->currency_symbol ?? '₹';

        return $symbol.number_format((float) $amount, 2);
    }
}
