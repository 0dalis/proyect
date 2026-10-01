<?php

namespace App\Providers;

use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * Telescope (/telescope): solo para Super Admins de la plataforma, que inician
 * sesión en /intern/web/services/1 (guard super_admin). Ningún usuario de empresa entra,
 * ni siquiera en local.
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Contraseñas, PIN y cookies nunca quedan guardados, en ningún entorno.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestParameters([
            '_token',
            'password',
            'password_confirmation',
            'current_password',
            'pin',
            'pin_confirmation',
        ]);

        Telescope::hideRequestHeaders([
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'x-kiosk-token',
        ]);

        Telescope::hideResponseParameters(['token']);
    }

    /**
     * Reemplaza la regla de Telescope que abre el acceso a todos en local.
     */
    protected function authorization(): void
    {
        $this->gate();

        Telescope::auth(fn (Request $request) => Gate::forUser($request->user('super_admin'))->check('viewTelescope'));
    }

    protected function gate(): void
    {
        Gate::define('viewTelescope', fn (?SuperAdmin $admin = null) => $admin instanceof SuperAdmin);
    }
}
