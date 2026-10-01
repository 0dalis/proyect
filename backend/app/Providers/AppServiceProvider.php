<?php

namespace App\Providers;

use App\Billing\BillingGateway;
use App\Billing\DemoBillingGateway;
use App\Billing\StripeBillingGateway;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Company;
use App\Models\Plan;
use App\Models\SuperAdmin;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantManager::class);

        // Sin claves de Stripe solo se permite el modo demo fuera de producción
        $this->app->bind(BillingGateway::class, fn ($app) => filled(config('cashier.secret')) || $app->isProduction()
            ? $app->make(StripeBillingGateway::class)
            : $app->make(DemoBillingGateway::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // La empresa (no el usuario) es el cliente de Stripe
        Cashier::useCustomerModel(Company::class);

        // La API responde los recursos sin envolverlos en "data", igual que el login
        JsonResource::withoutWrapping();

        // El dueño tiene todos los permisos de su empresa, incluso los que se agreguen después
        Gate::before(fn ($user) => $user instanceof User && $user->is_owner ? true : null);

        $this->defineRoleGates();
        $this->auditSuperAdmins();
    }

    /**
     * Bitácora del Super Admin: inicios de sesión y cambios a planes y a otros
     * Super Admins hechos desde el panel.
     */
    private function auditSuperAdmins(): void
    {
        Event::listen(Login::class, function (Login $event) {
            if ($event->guard === 'super_admin') {
                SuperAdminAuditLog::record('login', 'Inició sesión en el panel', admin: $event->user);
            }
        });

        $fromPanel = fn () => Auth::guard('super_admin')->check();

        Plan::created(fn (Plan $plan) => $fromPanel() && SuperAdminAuditLog::record('plan.created', "Creó el plan {$plan->name}"));
        Plan::updated(function (Plan $plan) use ($fromPanel) {
            $changes = collect($plan->getChanges())->except('updated_at');

            if ($fromPanel() && $changes->isNotEmpty()) {
                SuperAdminAuditLog::record('plan.updated', "Editó el plan {$plan->name}: ".$changes->keys()->implode(', '), properties: [
                    'before' => collect($plan->getOriginal())->only($changes->keys())->all(),
                    'after' => $changes->all(),
                ]);
            }
        });
        Plan::deleted(fn (Plan $plan) => $fromPanel() && SuperAdminAuditLog::record('plan.deleted', "Eliminó el plan {$plan->name}"));

        SuperAdmin::created(fn (SuperAdmin $admin) => $fromPanel() && SuperAdminAuditLog::record('super_admin.created', "Creó al Super Admin {$admin->email}"));
        SuperAdmin::updated(function (SuperAdmin $admin) use ($fromPanel) {
            // El secreto de 2FA y el remember token tienen su propio registro (o ninguno)
            $changes = collect($admin->getChanges())->except(['updated_at', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes']);

            if ($fromPanel() && $changes->isNotEmpty()) {
                SuperAdminAuditLog::record('super_admin.updated', "Editó al Super Admin {$admin->email}: ".$changes->keys()->implode(', '));
            }
        });
        SuperAdmin::deleted(fn (SuperAdmin $admin) => $fromPanel() && SuperAdminAuditLog::record('super_admin.deleted', "Eliminó al Super Admin {$admin->email}"));
    }

    /**
     * Reglas por rol que se usan en las rutas con `can:`. Los permisos
     * (employees.view, etc.) los resuelve Spatie; estas cubren lo que no es
     * un permiso configurable.
     */
    private function defineRoleGates(): void
    {
        // Solo el dueño (Gate::before ya le responde que sí; a los demás, no)
        Gate::define('owner', fn (User $user) => false);

        // Dueño o administrador (suscripción, datos de la empresa)
        Gate::define('admin-or-owner', fn (User $user) => $user->hasRole(Role::Admin->value));

        // Revisar solicitudes: justificaciones/retardos o vacaciones
        Gate::define('review-requests', fn (User $user) => $user->can(Permission::RequestsApprove->value)
            || $user->can(Permission::VacationsApprove->value));
    }
}
