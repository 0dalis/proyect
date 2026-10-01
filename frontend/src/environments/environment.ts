/**
 * En producción el panel y la API se sirven bajo el mismo dominio
 * (proxy inverso), igual que en desarrollo.
 */
export const environment = {
  production: true,
  apiUrl: '/api',
  csrfCookieUrl: '/sanctum/csrf-cookie',
  /**
   * Sesión por inactividad. idleMinutes debe ser igual a SESSION_LIFETIME
   * de Laravel (backend/.env).
   */
  session: {
    idleMinutes: 120,
    /** Aviso antes de cerrar la sesión. */
    warnMinutes: 5,
    /** Cada cuánto, como máximo, se avisa a Laravel que el usuario sigue activo. */
    pingMinutes: 5,
  },
};
