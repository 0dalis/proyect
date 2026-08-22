<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Middleware\Concerns\InteractsWithInactivity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminPanel
{
    use InteractsWithInactivity;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->hasRole('super-admin')) {
            return redirect()->route('landing');
        }

        if (session('last_activity_time') === null) {
            $this->expireSession();

            return redirect()->route('login')->with(
                'status',
                'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.'
            );
        }

        if ($this->inactivityExpired()) {
            $this->expireSession();

            return redirect()->route('login')->with(
                'status',
                'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.'
            );
        }

        $this->touchActivity();

        return $next($request);
    }
}
