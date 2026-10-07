import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { RoleName } from '../models';
import { AuthService } from '../services/auth.service';

/**
 * Reglas de acceso de una ruta del panel, declaradas en `data.access`:
 *
 *   data: { access: { permission: 'reports.view', module: 'payroll', roles: ['owner'] } }
 *
 * Todas las que se indiquen deben cumplirse. El backend vuelve a validar;
 * esto solo evita mostrar pantallas que responderían 403.
 */
export interface RouteAccess {
  permission?: string;
  roles?: RoleName[];
  module?: 'payroll' | 'bonuses';
  requiresEmployee?: boolean;
}

export function canAccess(auth: AuthService, access: RouteAccess | undefined): boolean {
  if (!access) {
    return true;
  }
  if (access.permission && !auth.can(access.permission)) {
    return false;
  }
  if (access.roles && !auth.hasRole(...access.roles)) {
    return false;
  }
  if (access.module && !auth.moduleEnabled(access.module)) {
    return false;
  }
  return !access.requiresEmployee || !!auth.user()?.employee;
}

export const accessGuard: CanActivateFn = async (route) => {
  const auth = inject(AuthService);
  // inject() solo funciona antes del primer await (contexto de inyección)
  const router = inject(Router);
  await auth.loadUser();

  return canAccess(auth, route.data['access']) ? true : router.createUrlTree(['/panel']);
};
