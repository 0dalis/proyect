<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea las rutas de un módulo opcional (sueldos, bonos) si el plan no lo
 * incluye (Free, Básico) o el dueño lo apagó.
 */
class EnsureModuleEnabled
{
    public function __construct(private TenantManager $tenants) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $company = $this->tenants->currentOrFail();

        if (! $company->plan->includes_payroll) {
            return response()->json([
                'message' => "Tu plan {$company->plan->name} no incluye pre-nómina ni bonos. Mejora tu plan para usarlos.",
                'code' => 'module_not_in_plan',
            ], 403);
        }

        if (! $company->moduleAvailable($module)) {
            return response()->json([
                'message' => 'Este módulo está desactivado. El dueño puede activarlo en Configuración.',
                'code' => 'module_disabled',
            ], 403);
        }

        return $next($request);
    }
}
