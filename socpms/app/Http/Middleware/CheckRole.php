<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        Log::info('CheckRole middleware executing', [
            'user' => Auth::check() ? Auth::id() : 'not authenticated',
            'required_role' => $role,
            'user_type' => Auth::check() ? Auth::user()->user_type : 'none',
            'path' => $request->path()
        ]);

        if (!Auth::check() || Auth::user()->user_type !== $role) {
            return redirect('/');
        }

        return $next($request);
    }
}
