<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Support\PlanSeats;
use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Va después de auth:sanctum. Conecta la BD de la empresa del usuario y
 * aplica las reglas de acceso (bloqueo, estado de la empresa, web vs app,
 * límite de empleados del plan).
 */
class InitializeTenancy
{
    /**
     * Lo único disponible mientras el dueño está en el modal de bienvenida
     * (la empresa aún no tiene base de datos).
     */
    private const ONBOARDING_ROUTES = [
        'web.onboarding.*',
        'web.sesion.perfil.actual',
        'web.sesion.logout',
        'web.sesion.ping',
    ];

    /**
     * Lo único disponible con contraseña temporal: cambiarla y crear el PIN.
     */
    private const FIRST_ACCESS_ROUTES = [
        'web.sesion.perfil.actual',
        'web.sesion.perfil.primer-acceso',
        'web.sesion.logout',
        'web.sesion.ping',
    ];

    public function __construct(private TenantManager $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = $user->company;

        if ($user->isBlocked()) {
            return $this->deny('Tu acceso fue desactivado. Contacta a tu empresa.', 'user_blocked');
        }

        if (! $user->hasVerifiedEmail()) {
            return $this->deny('Confirma tu correo para continuar.', 'email_not_verified');
        }

        if ($company->status === CompanyStatus::Onboarding && $user->is_owner) {
            return $request->routeIs(...self::ONBOARDING_ROUTES)
                ? $next($request)
                : $this->deny('Termina de configurar tu empresa para continuar.', 'onboarding_required');
        }

        if ($company->status === CompanyStatus::DeletionPending) {
            return $this->deny('Tu empresa pidió eliminar sus datos de AsistControl. El acceso está cerrado.', 'company_deleted');
        }

        if (! $company->status->canOperate()) {
            return $this->deny('La cuenta de la empresa no está activa: '.$company->status->label().'.', 'company_inactive');
        }

        $this->tenants->connect($company);

        if (PlanSeats::userIsLocked($user)) {
            return $this->deny('Tu empresa superó el límite de empleados de su plan y tu acceso quedó en pausa.', 'employee_locked');
        }

        $client = $request->header('X-Client', 'web');

        if ($client === 'app' && ! $user->app_access) {
            return $this->deny('No tienes acceso a la app.', 'app_access_denied');
        }

        if ($client === 'web' && ! $this->canUseWeb($user)) {
            return $this->deny('Tu empresa solo permite registrar asistencia desde la app.', 'web_access_denied');
        }

        if ($user->must_change_password && ! $request->routeIs(...self::FIRST_ACCESS_ROUTES)) {
            return $this->deny('Cambia tu contraseña temporal y crea tu PIN para continuar.', 'password_change_required');
        }

        return $next($request);
    }

    private function canUseWeb($user): bool
    {
        if (! $user->web_access) {
            return false;
        }

        return $user->primaryRole() !== Role::Employee || $user->company->employees_can_use_web;
    }

    private function deny(string $message, string $code): Response
    {
        return response()->json(['message' => $message, 'code' => $code], 403);
    }
}
