<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckScreenLocked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip check if user is not authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        // Skip check if already on lock screen or unlock endpoint
        if ($request->routeIs('screen.lock.*')) {
            return $next($request);
        }

        // Skip check for logout
        if ($request->routeIs('logout')) {
            return $next($request);
        }

        // Check if screen is locked in session
        if (session('screen_locked')) {
            return redirect()->route('screen.lock.show');
        }

        return $next($request);
    }
}
