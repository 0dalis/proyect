import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Login y registro: si ya hay sesión, va directo al panel.
 */
export const guestGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);

  return (await auth.loadUser()) ? inject(Router).createUrlTree(['/panel']) : true;
};
