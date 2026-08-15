import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { PublicServicesService } from '../services/public/public-services.service';
import { InactivityService } from '../services/public/inactivity.service';
import { catchError, map, of } from 'rxjs';

const HEARTBEAT_INTERVAL = 5 * 60 * 1000;
let heartbeatTimer: ReturnType<typeof setInterval> | null = null;

function clearSession(inactivity: InactivityService, router: Router) {
  clearHeartbeat();
  inactivity.stopWatching();
  localStorage.removeItem('is_logged_in');
  localStorage.removeItem('company_inactive');
  router.navigate(['/login']);
}

function startHeartbeat(auth: PublicServicesService, inactivity: InactivityService, router: Router) {
  clearHeartbeat();
  heartbeatTimer = setInterval(() => {
    auth.getUserPermissions().subscribe({
      error: () => clearSession(inactivity, router)
    });
  }, HEARTBEAT_INTERVAL);
}

function clearHeartbeat() {
  if (heartbeatTimer !== null) {
    clearInterval(heartbeatTimer);
    heartbeatTimer = null;
  }
}

export const authGuard: CanActivateFn = (route, state) => {
  const auth = inject(PublicServicesService);
  const router = inject(Router);
  const inactivity = inject(InactivityService);

  const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';
  if (!isLoggedIn) {
    inactivity.stopWatching();
    router.navigate(['/login']);
    return of(false);
  }

  if (localStorage.getItem('company_inactive') === 'true') {
    router.navigate(['/completecompany']);
    return of(false);
  }

  return auth.getUserPermissions().pipe(
    map((userSession) => {
      if (userSession && userSession.company_inactive) {
        localStorage.setItem('company_inactive', 'true');
        inactivity.stopWatching();
        router.navigate(['/completecompany']);
        return false;
      }

      if (userSession && userSession.roles.includes('admin')) {
        localStorage.removeItem('company_inactive');
        inactivity.startWatching();
        startHeartbeat(auth, inactivity, router);
        return true;
      }

      clearSession(inactivity, router);
      return false;
    }),
    catchError((error) => {
      clearSession(inactivity, router);
      return of(false);
    })
  );
};