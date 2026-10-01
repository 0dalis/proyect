<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Herramientas internas (Telescope): sin sesión de Super Admin, lleva al
 * login de /intern/web/services/1 y regresa aquí al entrar.
 */
class RequireSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('super_admin')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Solo para Super Admin.'], 403)
                : redirect()->guest(route('filament.superadmin.auth.login'));
        }

        return $next($request);
    }
}
