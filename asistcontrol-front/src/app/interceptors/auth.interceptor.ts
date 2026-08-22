import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

const INACTIVITY_TIMEOUT = 60 * 60 * 1000;
const LAST_ACTIVITY_KEY = 'last_activity';

let listenersRegistered = false;

function getCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
  return match ? decodeURIComponent(match[3]) : null;
}

function trackActivity(): void {
  localStorage.setItem(LAST_ACTIVITY_KEY, String(Date.now()));
}

function clearSession(): void {
  localStorage.removeItem('is_logged_in');
  localStorage.removeItem('company_inactive');
  localStorage.removeItem(LAST_ACTIVITY_KEY);
}

function sessionExpired(): boolean {
  if (localStorage.getItem('is_logged_in') !== 'true') {
    return false;
  }
  const lastActivity = Number(localStorage.getItem(LAST_ACTIVITY_KEY)) || 0;
  return lastActivity > 0 && Date.now() - lastActivity > INACTIVITY_TIMEOUT;
}

function forceLogout(router: Router): void {
  clearSession();
  router.navigate(['/login']);
}

function registerActivityListeners(): void {
  if (listenersRegistered) {
    return;
  }
  listenersRegistered = true;

  const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
  const track = () => trackActivity();

  events.forEach((eventName) => document.addEventListener(eventName, track));
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      track();
    }
  });
  window.addEventListener('focus', track);
}

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);

  registerActivityListeners();

  if (sessionExpired()) {
    forceLogout(router);
    return throwError(() => new Error('Sesión expirada por inactividad.'));
  }

  trackActivity();

  const xsrfToken = getCookie('XSRF-TOKEN');
  const isMutating = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(req.method.toUpperCase());

  const clonedRequest = req.clone({
    withCredentials: true,
    setHeaders: isMutating && xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}
  });

  return next(clonedRequest).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 || error.status === 403) {
        forceLogout(router);
      }
      return throwError(() => error);
    })
  );
};
