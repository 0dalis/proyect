/**
 * "Hace un momento", "hace 5 min", "hace 3 h", "ayer" o la fecha corta.
 */
export function timeAgo(value: string | Date, now: Date = new Date()): string {
  const date = typeof value === 'string' ? new Date(value) : value;
  const minutes = Math.floor((now.getTime() - date.getTime()) / 60_000);

  if (minutes < 1) {
    return 'hace un momento';
  }
  if (minutes < 60) {
    return `hace ${minutes} min`;
  }
  const hours = Math.floor(minutes / 60);
  if (hours < 24 && date.getDate() === now.getDate()) {
    return `hace ${hours} h`;
  }
  const yesterday = new Date(now);
  yesterday.setDate(now.getDate() - 1);
  if (date.toDateString() === yesterday.toDateString()) {
    return 'ayer';
  }
  return date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' });
}
