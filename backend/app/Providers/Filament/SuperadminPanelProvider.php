<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RequireSuperAdminTwoFactor;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperadminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('superadmin')
            ->path('intern/web/services/1')
            ->viteTheme(['resources/css/filament/superadmin/theme.css', 'resources/js/filament/dashboard.js'])
            ->authGuard('super_admin')
            ->login()
            ->profile(isSimple: false)
            // 2FA opcional con app (Google Authenticator, Authy...) y códigos de
            // recuperación. Cada Super Admin la activa en Perfil; si en Ajustes se
            // vuelve obligatoria, RequireSuperAdminTwoFactor manda a configurarla.
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName('AsistControl Super Admin')],
            )
            ->brandName('AsistControl · Super Admin')
            // Paleta corporativa: Azul Pizarra, Gris Hielo y estados semánticos
            ->colors([
                'primary' => Color::hex('#3b82f6'),
                'gray' => Color::Slate,
                'success' => Color::hex('#10b981'),
                'warning' => Color::hex('#f59e0b'),
                'danger' => Color::hex('#ef4444'),
                'info' => Color::hex('#06b6d4'),
            ])
            ->font('Inter')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->darkMode(false)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                RequireSuperAdminTwoFactor::class,
            ]);
    }
}
