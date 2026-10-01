import { Component, computed, input, signal } from '@angular/core';
import { ChartComponent, PieSlice } from '../chart/chart.component';

export interface PieChartSlice {
  key: string;
  label: string;
  value: number;
  /** Hex o variable CSS. */
  color: string;
  /** Icono de Bootstrap Icons en la leyenda, para no depender solo del color. */
  icon: string;
}

/**
 * Gráfica de pastel (Chart.js) con leyenda propia (cantidad y porcentaje,
 * con icono) y vista de tabla. Las rebanadas en cero no se dibujan, pero sí
 * aparecen en la tabla.
 */
@Component({
  selector: 'app-pie-chart',
  imports: [ChartComponent],
  templateUrl: './pie-chart.component.html',
  styleUrl: './pie-chart.component.scss',
})
export class PieChartComponent {
  readonly slices = input.required<PieChartSlice[]>();
  readonly description = input('Distribución');
  readonly height = input(240);
  readonly unit = input('');

  protected readonly showTable = signal(false);

  protected readonly total = computed(() => this.slices().reduce((sum, s) => sum + s.value, 0));
  protected readonly visible = computed(() => this.slices().filter((s) => s.value > 0));
  protected readonly chartSlices = computed<PieSlice[]>(() =>
    this.visible().map(({ label, value, color }) => ({ label, value, color })),
  );

  protected percent(value: number): number {
    return this.total() ? Math.round((value / this.total()) * 100) : 0;
  }
}
