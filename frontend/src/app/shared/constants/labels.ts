export const WEEKDAYS = [
  { value: 1, short: 'L', label: 'Lunes' },
  { value: 2, short: 'M', label: 'Martes' },
  { value: 3, short: 'X', label: 'Miércoles' },
  { value: 4, short: 'J', label: 'Jueves' },
  { value: 5, short: 'V', label: 'Viernes' },
  { value: 6, short: 'S', label: 'Sábado' },
  { value: 7, short: 'D', label: 'Domingo' },
];

export const STATUS_LABELS: Record<string, string> = {
  on_time: 'A tiempo',
  late: 'Retardo',
  absent: 'Falta',
  early_leave: 'Salida anticipada',
  pending: 'Pendiente',
  approved: 'Aprobada',
  rejected: 'Rechazada',
  active: 'Activo',
  inactive: 'Inactivo',
  terminated: 'Baja',
};

export const REQUEST_TYPE_LABELS: Record<string, string> = {
  justification: 'Justificación',
  late_arrival: 'Llegada tarde',
  early_departure: 'Salida anticipada',
  vacation: 'Vacaciones',
  leave: 'Permiso',
};

export const CHANNEL_LABELS: Record<string, string> = {
  app_biometric: 'App · biometría',
  app_pin: 'App · PIN',
  kiosk_qr: 'Kiosko · QR',
  kiosk_pin: 'Kiosko · PIN',
  manual: 'Manual',
};
