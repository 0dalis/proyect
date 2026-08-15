<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $timeout = (int) config('session.lifetime', 60) * 60;
        $lastActivity = session('last_activity_time');

        if ($lastActivity && (time() - (int) $lastActivity) > $timeout) {
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.',
                ], 401);
            }

            return redirect()->route('login')->with(
                'status',
                'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.'
            );
        }

        session(['last_activity_time' => time()]);

        return $next($request);
    }
}
