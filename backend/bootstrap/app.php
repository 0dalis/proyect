<?php

use App\Http\Middleware\AuthenticateKiosk;
use App\Http\Middleware\EnsureEmployeeWithinPlan;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\InitializeTenancy;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Panel web: sesión en cookie HttpOnly + CSRF (Sanctum SPA). La app
        // móvil y el kiosko siguen usando tokens.
        $middleware->statefulApi();
        $middleware->appendToGroup('api', SecurityHeaders::class);

        // Stripe firma sus webhooks (STRIPE_WEBHOOK_SECRET); no traen token CSRF
        $middleware->validateCsrfTokens(except: ['stripe/*']);

        $middleware->alias([
            'tenant' => InitializeTenancy::class,
            'kiosk' => AuthenticateKiosk::class,
            'module' => EnsureModuleEnabled::class,
            'seats' => EnsureEmployeeWithinPlan::class,
        ]);

        // La BD de la empresa debe estar conectada antes de resolver los
        // modelos de la ruta (/employees/{employee}, etc.)
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: InitializeTenancy::class);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: AuthenticateKiosk::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Sin sesión válida (la cookie venció tras SESSION_LIFETIME minutos sin
        // actividad, o nunca se inició): siempre el mismo formato, para que
        // Angular cierre la sesión y lleve al login.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Tu sesión expiró. Vuelve a iniciar sesión.',
                'code' => 'session_expired',
            ], 401);
        });
    })->create();
