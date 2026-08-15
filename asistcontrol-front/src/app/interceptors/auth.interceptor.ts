import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

function getCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
  return match ? decodeURIComponent(match[3]) : null;
}

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const xsrfToken = getCookie('XSRF-TOKEN');
  const isMutating = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(req.method.toUpperCase());

  const clonedRequest = req.clone({
    withCredentials: true,
    setHeaders: isMutating && xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}
  });

  return next(clonedRequest).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 || error.status === 403) {
        localStorage.removeItem('is_logged_in');
        localStorage.removeItem('company_inactive');
        router.navigate(['/login']);
      }
      return throwError(() => error);
    })
  );
};
