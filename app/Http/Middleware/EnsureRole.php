<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Usage: ->middleware('role:staff') or 'role:member' or 'role:super_admin'. "staff" means any gym staff role. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        $allowed = collect($roles)->contains(fn ($role) => $role === 'staff' ? $user->isStaff() : $user->hasRole($role));

        if (! $allowed) {
            return redirect($user->homeRoute());
        }

        return $next($request);
    }
}
