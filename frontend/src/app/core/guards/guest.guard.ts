import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Login y registro: si ya hay sesión, va directo al panel.
 */
export const guestGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);
  // inject() solo funciona antes del primer await (contexto de inyección)
  const router = inject(Router);

  return (await auth.loadUser()) ? router.createUrlTree(['/panel']) : true;
};
