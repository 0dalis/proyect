import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { guestGuard } from './core/guards/guest.guard';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./pages/landing/landing.component').then((m) => m.LandingComponent),
  },
  {
    path: 'registro',
    title: 'Crear cuenta',
    canActivate: [guestGuard],
    loadComponent: () =>
      import('./pages/auth/register/register.component').then((m) => m.RegisterComponent),
  },
  {
    path: 'login',
    title: 'Iniciar sesión',
    canActivate: [guestGuard],
    loadComponent: () => import('./pages/auth/login/login.component').then((m) => m.LoginComponent),
  },
  {
    path: 'olvide-contrasena',
    title: 'Recuperar contraseña',
    canActivate: [guestGuard],
    loadComponent: () =>
      import('./pages/auth/forgot-password/forgot-password.component').then(
        (m) => m.ForgotPasswordComponent,
      ),
  },
  {
    // Enlace del correo "Restablece tu contraseña"
    path: 'restablecer-contrasena',
    title: 'Restablecer contraseña',
    loadComponent: () =>
      import('./pages/auth/reset-password/reset-password.component').then(
        (m) => m.ResetPasswordComponent,
      ),
  },
  {
    // Enlace del correo de confirmación (firmado por Laravel)
    path: 'verificar-cuenta/:id/:hash',
    title: 'Confirmar cuenta',
    loadComponent: () =>
      import('./pages/auth/verify-account/verify-account.component').then(
        (m) => m.VerifyAccountComponent,
      ),
  },
  {
    // Enlace único del último correo, tras eliminar los datos de una empresa
    path: 'valoracion/:token',
    title: 'Valora AsistControl',
    loadComponent: () =>
      import('./pages/feedback/feedback.component').then((m) => m.FeedbackComponent),
  },
  {
    path: 'kiosko',
    title: 'Kiosko',
    loadComponent: () => import('./pages/kiosk/kiosk.component').then((m) => m.KioskComponent),
  },
  {
    path: 'legal',
    loadComponent: () =>
      import('./pages/legal/legal-layout/legal-layout.component').then(
        (m) => m.LegalLayoutComponent,
      ),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'terminos' },
      {
        path: 'terminos',
        title: 'Términos de uso',
        loadComponent: () =>
          import('./pages/legal/terms/terms.component').then((m) => m.TermsComponent),
      },
      {
        path: 'privacidad',
        title: 'Aviso de privacidad',
        loadComponent: () =>
          import('./pages/legal/privacy/privacy.component').then((m) => m.PrivacyComponent),
      },
      {
        path: 'cookies',
        title: 'Política de cookies',
        loadComponent: () =>
          import('./pages/legal/cookies/cookies.component').then((m) => m.CookiesComponent),
      },
    ],
  },
  {
    path: 'panel',
    canActivate: [authGuard],
    canActivateChild: [authGuard],
    loadComponent: () =>
      import('./layout/panel-layout/panel-layout.component').then((m) => m.PanelLayoutComponent),
    loadChildren: () => import('./pages/panel/panel.routes').then((m) => m.PANEL_ROUTES),
  },
  { path: '**', redirectTo: '' },
];
