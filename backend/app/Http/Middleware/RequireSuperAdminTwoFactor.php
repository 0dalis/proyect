<?php

namespace App\Http\Middleware;

use App\Models\SuperAdmin;
use App\Models\SystemSetting;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 2FA del Super Admin: es opcional hasta que se vuelve obligatoria en
 * Ajustes. Entonces quien aún no la tiene solo puede entrar a su Perfil
 * (donde la configura) o cerrar sesión.
 */
class RequireSuperAdminTwoFactor
{
    public const SETTING = 'super_admin_mfa_required';

    private const ALLOWED_ROUTES = ['filament.superadmin.auth.profile', 'filament.superadmin.auth.logout'];

    public static function isRequired(): bool
    {
        return (bool) SystemSetting::get(self::SETTING, false);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('super_admin');

        if (! $admin instanceof SuperAdmin
            || $admin->hasTwoFactor()
            || ! self::isRequired()
            || in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        Notification::make()
            ->title('Activa la verificación en dos pasos')
            ->body('Es obligatoria para entrar al panel. Configúrala en "Autenticación con app" y guarda tus códigos de recuperación.')
            ->warning()
            ->persistent()
            ->send();

        return redirect()->route('filament.superadmin.auth.profile');
    }
}
