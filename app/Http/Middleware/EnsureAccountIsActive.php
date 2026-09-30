<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && (! $user->is_active || ($user->gym && ! $user->gym->isActive()))) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors([
                'email' => $user->is_active ? 'This gym account is suspended. Contact support.' : 'Your account is disabled.',
            ]);
        }

        return $next($request);
    }
}
