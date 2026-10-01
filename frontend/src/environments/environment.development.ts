/**
 * En desarrollo, `ng serve` reenvía /api y /sanctum a Laravel (proxy.conf.json),
 * así el panel y la API comparten origen y la cookie de sesión es de primera parte.
 */
export const environment = {
  production: false,
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
