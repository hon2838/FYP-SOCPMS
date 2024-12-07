<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        Log::info('RoleMiddleware executing', [
            'user' => Auth::check() ? Auth::id() : 'not authenticated',
            'required_role' => $role,
            'user_type' => Auth::check() ? Auth::user()->user_type : 'none',
            'path' => $request->path()
        ]);

        if (!Auth::check() || Auth::user()->user_type !== $role) {
            Log::warning('RoleMiddleware access denied', [
                'user' => Auth::check() ? Auth::id() : 'not authenticated',
                'required_role' => $role,
                'user_type' => Auth::check() ? Auth::user()->user_type : 'none'
            ]);
            return redirect('/');
        }

        Log::info('RoleMiddleware access granted', [
            'user' => Auth::id(),
            'role' => $role
        ]);

        return $next($request);
    }
}
