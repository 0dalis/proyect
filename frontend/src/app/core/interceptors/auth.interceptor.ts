import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { ApiService } from '../services/api.service';

/**
 * Marca las llamadas a la API como JSON y del canal web. La sesión viaja en
 * la cookie (no hay token que agregar) y el CSRF lo pone HttpClient. Las
 * llamadas del kiosko llevan su propio encabezado X-Kiosk-Token.
 */
export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const api = inject(ApiService);

  if (!api.isApiUrl(request.url)) {
    return next(request);
  }

  const isKiosk = request.headers.has('X-Kiosk-Token');

  return next(
    request.clone({
      withCredentials: true,
      setHeaders: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(isKiosk ? {} : { 'X-Client': 'web' }),
      },
    }),
  );
};
