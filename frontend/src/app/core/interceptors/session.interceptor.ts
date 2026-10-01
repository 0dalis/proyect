import { HttpContextToken, HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, from, switchMap, throwError } from 'rxjs';
import { ApiService } from '../services/api.service';
import { AuthService } from '../services/auth.service';
import { ToastService } from '../services/toast.service';

/** Mensaje para el usuario según el motivo del cierre de sesión. */
const REASONS: Record<string, string> = {
  expired: 'Tu sesión expiró. Vuelve a iniciar sesión.',
  session_expired: 'Tu sesión expiró por inactividad. Vuelve a iniciar sesión.',
  user_blocked: 'Tu acceso fue desactivado. Contacta a tu empresa.',
  company_inactive: 'La cuenta de tu empresa no está activa.',
  web_access_denied: 'Tu empresa solo permite usar la app móvil.',
  email_not_verified: 'Confirma tu correo para continuar.',
  company_deleted: 'Tu empresa pidió eliminar sus datos de AsistControl. El acceso está cerrado.',
  employee_locked: 'Tu empresa superó el límite de empleados de su plan y tu acceso quedó en pausa. Contacta a tu empresa.',
};

/**
 * Códigos del backend que significan "tu sesión ya no puede seguir".
 */
const SESSION_ENDED_CODES = [
  'user_blocked',
  'company_inactive',
  'web_access_denied',
  'email_not_verified',
  'company_deleted',
  'employee_locked',
];

/** Marca una petición que ya se reintentó tras renovar el token CSRF. */
const CSRF_RETRIED = new HttpContextToken<boolean>(() => false);

/**
 * - 419 (token CSRF vencido): pide uno nuevo y reintenta una sola vez.
 * - 401 o cuenta bloqueada: cierra la sesión y lleva al login con el motivo,
 *   en lugar de dejar pantallas a medias.
 */
export const sessionInterceptor: HttpInterceptorFn = (request, next) => {
  const auth = inject(AuthService);
  const api = inject(ApiService);
  const router = inject(Router);
  const toast = inject(ToastService);

  return next(request).pipe(
    catchError((error: unknown) => {
      if (!(error instanceof HttpErrorResponse)) {
        return throwError(() => error);
      }

      if (error.status === 419 && !request.context.get(CSRF_RETRIED)) {
        return from(api.csrfCookie()).pipe(
          switchMap(() =>
            next(request.clone({ context: request.context.set(CSRF_RETRIED, true) })),
          ),
        );
      }

      const isKiosk = request.headers.has('X-Kiosk-Token');
      const isAuthCall = request.url.includes('/auth/');
      const code = error.error?.code as string | undefined;
      const sessionEnded =
        error.status === 401 ||
        (error.status === 403 && !!code && SESSION_ENDED_CODES.includes(code));

      if (sessionEnded && auth.user() && !isKiosk && !isAuthCall) {
        const reason = code ?? 'expired';
        auth.clear();
        toast.warning(REASONS[reason] ?? REASONS['expired'], {
          title: 'Sesión cerrada',
          duration: 7000,
        });
        router.navigate(['/login']);
      }

      return throwError(() => error);
    }),
  );
};
