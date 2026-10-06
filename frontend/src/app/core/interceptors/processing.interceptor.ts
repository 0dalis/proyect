import { HttpContext, HttpContextToken, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { finalize } from 'rxjs';
import { ProcessingService } from '../services/processing.service';

/** Petición de fondo (marcar como leída…): no muestra "Procesando…". */
export const SILENT_REQUEST = new HttpContextToken<boolean>(() => false);

export function silent(): HttpContext {
  return new HttpContext().set(SILENT_REQUEST, true);
}

/**
 * Muestra "Procesando…" mientras el servidor responde a una acción del
 * usuario: lo que guarda o elimina (POST/PUT/PATCH/DELETE) y las descargas.
 * Las consultas (GET) no, porque las pantallas ya muestran su esqueleto de
 * carga; el kiosko tampoco, porque tiene su propia pantalla.
 */
export const processingInterceptor: HttpInterceptorFn = (request, next) => {
  const isAction = request.method !== 'GET' || request.responseType === 'blob';
  if (!isAction || request.context.get(SILENT_REQUEST) || request.headers.has('X-Kiosk-Token')) {
    return next(request);
  }

  const processing = inject(ProcessingService);
  processing.begin();
  return next(request).pipe(finalize(() => processing.end()));
};
