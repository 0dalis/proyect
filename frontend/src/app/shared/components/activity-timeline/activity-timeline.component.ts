import { DatePipe } from '@angular/common';
import { Component, input, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ActivityChange, ActivityLogEntry } from '../../../core/models';
import { ACTION_STYLES, DEFAULT_ACTION_STYLE, FIELD_LABELS } from '../../constants/activity';

/**
 * Lista de registros de la bitácora con quién, qué y cuándo. Los cambios de
 * campos se despliegan al tocar cada registro.
 */
@Component({
  selector: 'app-activity-timeline',
  imports: [DatePipe, RouterLink],
  templateUrl: './activity-timeline.component.html',
  styleUrl: './activity-timeline.component.scss',
})
export class ActivityTimelineComponent {
  readonly entries = input.required<ActivityLogEntry[]>();
  readonly actions = input<Record<string, string>>({});
  /** Mostrar el enlace al empleado relacionado (en la bitácora general). */
  readonly linkEmployees = input(false);

  protected readonly open = signal<Set<number>>(new Set());

  protected actionLabel(action: string): string {
    return this.actions()[action] || action;
  }

  protected style(action: string) {
    return ACTION_STYLES[action] ?? DEFAULT_ACTION_STYLE;
  }

  /** Color del punto de la línea de tiempo según la acción. */
  protected dot(action: string): string {
    return this.style(action).dot;
  }

  protected toggle(entry: ActivityLogEntry): void {
    if (!entry.changes) {
      return;
    }
    const next = new Set(this.open());
    next.has(entry.id) ? next.delete(entry.id) : next.add(entry.id);
    this.open.set(next);
  }

  protected changes(entry: ActivityLogEntry): { field: string; change: ActivityChange }[] {
    return Object.entries(entry.changes ?? {}).map(([field, change]) => ({
      field: FIELD_LABELS[field] ?? field,
      change,
    }));
  }

  protected format(value: unknown): string {
    if (value === null || value === undefined || value === '') {
      return '—';
    }
    if (typeof value === 'boolean') {
      return value ? 'Sí' : 'No';
    }
    if (Array.isArray(value)) {
      return value.length ? value.join(', ') : '—';
    }
    if (typeof value === 'object') {
      return JSON.stringify(value);
    }
    const text = String(value);
    // Fechas ISO del servidor
    return /^\d{4}-\d{2}-\d{2}T/.test(text) ? new Date(text).toLocaleString('es-MX') : text;
  }
}
