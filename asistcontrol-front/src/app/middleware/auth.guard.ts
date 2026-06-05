import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { PublicServicesService } from '../services/public/public-services.service';
import { InactivityService } from '../services/public/inactivity.service';

export const authGuard: CanActivateFn = (route, state) => {

  const auth = inject(PublicServicesService);
  const router = inject(Router);
  const inactivity = inject(InactivityService);

  const token = auth.getToken();
  const user = auth.getUser();

  if (!token || !user || user.role !== 'admin') {
    inactivity.stopWatching();
    router.navigate(['/login']);
    return false;
  }

  inactivity.startWatching();
  return true;
};