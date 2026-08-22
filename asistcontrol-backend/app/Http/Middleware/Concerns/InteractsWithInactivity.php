<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Support\Facades\Auth;

trait InteractsWithInactivity
{
    /**
     * Determina si la sesión superó el tiempo máximo de inactividad.
     */
    protected function inactivityExpired(): bool
    {
        $lastActivity = session('last_activity_time');

        if ($lastActivity === null) {
            return false;
        }

        $timeout = (int) config('session.lifetime', 60) * 60;

        return (time() - (int) $lastActivity) > $timeout;
    }

    /**
     * Cierra la sesión del usuario por inactividad.
     */
    protected function expireSession(): void
    {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Actualiza el timestamp de la última actividad registrada.
     */
    protected function touchActivity(): void
    {
        session(['last_activity_time' => time()]);
    }
}
