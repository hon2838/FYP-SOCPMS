<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            return redirect(Auth::user()->user_type === 'admin' ? '/admin/dashboard' : '/user/dashboard');
        }

        return $next($request);
    }
}
