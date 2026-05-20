import { CanActivateChildFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { PublicServicesService } from '../services/public/public-services.service';
import { inactivitiservice } from '../services/public/inactiviti.service';

export const authGuard: CanActivateChildFn = (childRoute, state) => {

  const auth = inject(PublicServicesService);
  const router = inject(Router);
  const inactiviti = inject(inactivitiservice);

  const token = auth.getToken();
  const user = auth.getUser();

  if (!token || !user || user.role !== 'admin') {
    inactiviti.stopWatching();
    router.navigate(['/Login']);
    return false;
  }

  inactiviti.startWatching();
  return true;
};