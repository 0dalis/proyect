/**
 * Datos que aparecen en los documentos legales y pies de página.
 * Los valores entre corchetes son pendientes: complétalos antes de publicar.
 */
export const LEGAL = {
  product: 'AsistControl',
  provider: 'JALY SYSTEMS, S.A. de C.V.',
  providerShort: 'JALY SYSTEMS',
  /** Debe coincidir con config/legal.php del backend */
  version: '2026-09',
  updatedAt: '25 de septiembre de 2026',
  privacyEmail: '[correo para temas de privacidad]',
  supportEmail: '[correo de soporte]',
  address: '[domicilio de JALY SYSTEMS, S.A. de C.V.]',
  jurisdiction: '[ciudad y estado para jurisdicción]',
} as const;

export const LEGAL_LINKS = [
  { path: '/legal/terminos', label: 'Términos de uso' },
  { path: '/legal/privacidad', label: 'Aviso de privacidad' },
  { path: '/legal/cookies', label: 'Política de cookies' },
] as const;
