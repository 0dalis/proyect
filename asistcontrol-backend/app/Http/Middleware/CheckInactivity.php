<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Middleware\Concerns\InteractsWithInactivity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInactivity
{
    use InteractsWithInactivity;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return $next($request);
        }

        if ($this->inactivityExpired()) {
            $this->expireSession();

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

        $this->touchActivity();

        return $next($request);
    }
}
