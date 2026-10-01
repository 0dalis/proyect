import { HttpErrorResponse } from '@angular/common/http';

/**
 * Convierte la respuesta de error de Laravel en un mensaje legible.
 */
export function errorMessage(error: unknown): string {
  if (error instanceof HttpErrorResponse) {
    const errors = error.error?.errors as Record<string, string[]> | undefined;
    if (errors) {
      return Object.values(errors).flat().join(' ');
    }
    if (error.status === 0) {
      return 'No se pudo conectar con el servidor.';
    }
    return error.error?.message ?? 'Ocurrió un error inesperado.';
  }
  // Errores propios del navegador (Stripe, reCAPTCHA) ya traen un mensaje para el usuario
  if (error instanceof Error && error.message) {
    return error.message;
  }
  return 'Ocurrió un error inesperado.';
}

export function errorCode(error: unknown): string | null {
  return error instanceof HttpErrorResponse ? (error.error?.code ?? null) : null;
}
