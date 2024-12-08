<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        Log::debug('CheckRole middleware executed', [
            'user' => Auth::user(),
            'required_role' => $role,
            'is_authenticated' => Auth::check(),
        ]);

        if (!Auth::check()) {
            Log::warning('User not authenticated');
            return redirect('/');
        }

        if (Auth::user()->user_type !== $role) {
            Log::warning('User does not have required role', [
                'user_type' => Auth::user()->user_type,
                'required_role' => $role,
            ]);
            return redirect('/');
        }

        return $next($request);
    }
}
