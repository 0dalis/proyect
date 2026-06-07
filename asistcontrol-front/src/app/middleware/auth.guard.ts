import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { PublicServicesService } from '../services/public/public-services.service';
import { InactivityService } from '../services/public/inactivity.service';
import { catchError, map, of } from 'rxjs';

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
  return auth.getUserPermissions().pipe(
    map((userSession) => {
      if (userSession && userSession.roles.includes('admin')) {
        inactivity.startWatching();
        return true;
      }
      inactivity.stopWatching();
      localStorage.removeItem('is_logged_in');
      router.navigate(['/login']); 
      return false;
    }),
    catchError((error) => {
      localStorage.removeItem('is_logged_in');
      
      inactivity.stopWatching();
      router.navigate(['/login']);
      return of(false); 
    })
  );
};