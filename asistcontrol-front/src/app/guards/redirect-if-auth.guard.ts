import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const redirectIfAuthGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);

  const isLoggedIn = localStorage.getItem('is_logged_in') === 'true';

  if (isLoggedIn) {
    if (localStorage.getItem('company_inactive') === 'true') {
      router.navigate(['/completecompany']);
    } else {
      router.navigate(['/asistcontrol']);
    }
    return false;
  }

  return true;
};
