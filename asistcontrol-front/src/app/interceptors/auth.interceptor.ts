import { HttpInterceptorFn } from '@angular/common/http';

function getCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
  return match ? decodeURIComponent(match[3]) : null;
}

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const xsrfToken = getCookie('XSRF-TOKEN');
  const isMutating = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(req.method.toUpperCase());

  const clonedRequest = req.clone({
    withCredentials: true,
    setHeaders: isMutating && xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}
  });

  return next(clonedRequest);
};
