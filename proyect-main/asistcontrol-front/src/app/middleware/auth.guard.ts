import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { PublicServicesService } from '../services/public/public-services.service';
import { InactivityService } from '../services/public/inactivity.service';
import { catchError, map, of } from 'rxjs';

const ALLOWED_ROLES = ['admin', 'owner', 'super-admin'];

function clearSession(inactivity: InactivityService, router: Router) {
  inactivity.stopWatching();
  localStorage.removeItem('is_logged_in');
  localStorage.removeItem('company_inactive');
  localStorage.removeItem('last_activity');
  router.navigate(['/login']);
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

      if (userSession && userSession.roles.some((role: string) => ALLOWED_ROLES.includes(role))) {
        localStorage.removeItem('company_inactive');
        inactivity.startWatching();
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
