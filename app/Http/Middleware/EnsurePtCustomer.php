<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Advanced PT modules are only available to members whose training type is "Personal Training Customer". */
class EnsurePtCustomer
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->member?->isPt()) {
            return redirect()->route('portal.dashboard')->with('toast', 'Personal training features are available to PT customers only.');
        }

        return $next($request);
    }
}
