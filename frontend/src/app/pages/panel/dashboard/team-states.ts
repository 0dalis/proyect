import { TeamState } from '../../../core/models';

/**
 * Color e icono de cada estado del día. Verde, ámbar y rojo son los de
 * estado (a tiempo, retardo, sin registro); los que no trabajan hoy van en
 * grises. Cada uno lleva icono para no depender solo del color.
 */
export const TEAM_STATE_META: Record<TeamState, { color: string; icon: string }> = {
  on_time: { color: 'var(--color-status-good)', icon: 'check-lg' },
  late: { color: 'var(--color-status-warning)', icon: 'clock' },
  missing: { color: 'var(--color-status-critical)', icon: 'x-lg' },
  vacation: { color: '#06b6d4', icon: 'airplane' },
  leave: { color: '#8b5cf6', icon: 'file-earmark-check' },
  upcoming: { color: '#64748b', icon: 'hourglass-split' },
  rest: { color: '#94a3b8', icon: 'moon' },
  holiday: { color: '#cbd5e1', icon: 'calendar-heart' },
  no_shift: { color: '#e2e8f0', icon: 'question-lg' },
};

/** Orden de la gráfica y de las barras por oficina / turno. */
export const TEAM_STATE_ORDER: TeamState[] = [
  'on_time',
  'late',
  'missing',
  'vacation',
  'leave',
  'upcoming',
  'rest',
  'holiday',
  'no_shift',
];
