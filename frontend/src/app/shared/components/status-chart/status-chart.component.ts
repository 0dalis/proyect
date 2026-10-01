import { formatDate } from '@angular/common';
import { Component, computed, inject, input, LOCALE_ID, signal } from '@angular/core';
import { ChartComponent, ChartSeries } from '../chart/chart.component';

/**
 * Un punto por día (`day`) o con etiqueta propia (`label`, p. ej. "sep").
 * `excused` (vacaciones / permisos) es opcional.
 */
export interface StatusPoint {
  day?: string;
  label?: string;
  on_time: number;
  late: number;
  absent: number;
  excused?: number;
}

type SeriesKey = 'on_time' | 'late' | 'absent' | 'excused';

const ALL_SERIES: { key: SeriesKey; label: string; color: string }[] = [
  { key: 'on_time', label: 'A tiempo', color: 'var(--color-status-good)' },
  { key: 'late', label: 'Retardo', color: 'var(--color-status-warning)' },
  { key: 'absent', label: 'Falta', color: 'var(--color-status-critical)' },
  { key: 'excused', label: 'Vacaciones / permiso', color: 'var(--color-info)' },
];

/**
 * Asistencia por estado en el tiempo: una línea por estado (Chart.js), con
 * tooltip por día y vista de tabla accesible.
 */
@Component({
  selector: 'app-status-chart',
  imports: [ChartComponent],
  templateUrl: './status-chart.component.html',
  styleUrl: './status-chart.component.scss',
})
export class StatusChartComponent {
  private readonly locale = inject(LOCALE_ID);

  readonly data = input.required<StatusPoint[]>();
  /** Texto para lectores de pantalla, p. ej. "Asistencia de la semana". */
  readonly description = input('Entradas por día');
  readonly height = input(240);

  protected readonly showTable = signal(false);

  /** La serie de vacaciones solo aparece si hay datos. */
  protected readonly keys = computed(() =>
    ALL_SERIES.filter((s) => s.key !== 'excused' || this.data().some((p) => (p.excused ?? 0) > 0)),
  );

  protected readonly labels = computed(() => this.data().map((point) => this.shortLabel(point)));

  protected readonly series = computed<ChartSeries[]>(() =>
    this.keys().map((serie) => ({
      label: serie.label,
      color: serie.color,
      data: this.data().map((point) => point[serie.key] ?? 0),
    })),
  );

  protected readonly rows = computed(() =>
    this.data().map((point) => ({ point, long: this.longLabel(point) })),
  );

  protected value(point: StatusPoint, key: SeriesKey): number {
    return point[key] ?? 0;
  }

  private shortLabel(point: StatusPoint): string {
    return point.label ?? (point.day ? formatDate(point.day, 'd MMM', this.locale) : '');
  }

  private longLabel(point: StatusPoint): string {
    return point.label ?? (point.day ? formatDate(point.day, 'EEEE d MMM', this.locale) : '');
  }
}
