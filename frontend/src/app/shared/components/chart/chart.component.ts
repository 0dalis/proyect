import {
  afterNextRender,
  Component,
  effect,
  ElementRef,
  inject,
  input,
  OnDestroy,
  viewChild,
} from '@angular/core';
import {
  ArcElement,
  CategoryScale,
  Chart,
  ChartConfiguration,
  Filler,
  Legend,
  LinearScale,
  LineController,
  LineElement,
  PieController,
  PointElement,
  Tooltip,
} from 'chart.js';
import { ThemeService } from '../../../core/services/theme.service';

Chart.register(
  LineController,
  LineElement,
  PointElement,
  PieController,
  ArcElement,
  CategoryScale,
  LinearScale,
  Filler,
  Tooltip,
  Legend,
);

export interface ChartSeries {
  label: string;
  data: number[];
  /** Hex o variable CSS (var(--color-status-good)); se resuelve al dibujar. */
  color: string;
}

export interface PieSlice {
  label: string;
  value: number;
  color: string;
}

/**
 * Gráfica de Chart.js: de línea (una o varias series) o de pastel. Toma los
 * colores del tema y se vuelve a dibujar al cambiar entre claro y oscuro.
 *
 *   <app-chart type="line" [labels]="días" [series]="series" />
 *   <app-chart type="pie" [slices]="rebanadas" />
 */
@Component({
  selector: 'app-chart',
  template: `
    <div class="relative" [style.height.px]="height()">
      <canvas #canvas role="img" [attr.aria-label]="description()"></canvas>
    </div>
  `,
  host: { class: 'block' },
})
export class ChartComponent implements OnDestroy {
  private readonly theme = inject(ThemeService);
  private readonly host = inject(ElementRef<HTMLElement>);
  private readonly canvas = viewChild.required<ElementRef<HTMLCanvasElement>>('canvas');

  readonly type = input<'line' | 'pie'>('line');
  readonly labels = input<string[]>([]);
  readonly series = input<ChartSeries[]>([]);
  readonly slices = input<PieSlice[]>([]);
  readonly height = input(260);
  readonly description = input('Gráfica');
  /** Leyenda de Chart.js (desactívala si el componente trae la suya). */
  readonly legend = input(true);
  /** Texto después del valor en el tooltip, p. ej. " empleados". */
  readonly unit = input('');

  private chart: Chart | null = null;
  private ready = false;

  constructor() {
    afterNextRender(() => {
      this.ready = true;
      this.render();
    });

    // Redibuja con datos nuevos o al cambiar el tema / color de acento
    effect(() => {
      this.type();
      this.labels();
      this.series();
      this.slices();
      this.legend();
      this.theme.isDark();
      this.theme.accent();
      if (this.ready) {
        this.render();
      }
    });
  }

  ngOnDestroy(): void {
    this.chart?.destroy();
  }

  private render(): void {
    this.chart?.destroy();
    this.chart = new Chart(this.canvas().nativeElement, this.config());
  }

  private config(): ChartConfiguration {
    const text = this.css('--color-ink-soft', '#475569');
    const grid = this.css('--color-line', '#e2e8f0');
    const surface = this.css('--color-surface', '#ffffff');
    const font = { family: getComputedStyle(this.host.nativeElement).fontFamily, size: 12 };
    const unit = this.unit();
    const legend = {
      display: this.legend(),
      position: 'bottom' as const,
      labels: { color: text, font, usePointStyle: true, boxWidth: 8, padding: 14 },
    };

    if (this.type() === 'pie') {
      const slices = this.slices().filter((slice) => slice.value > 0);
      const total = slices.reduce((sum, slice) => sum + slice.value, 0);

      return {
        type: 'pie',
        data: {
          labels: slices.map((slice) => slice.label),
          datasets: [
            {
              data: slices.map((slice) => slice.value),
              backgroundColor: slices.map((slice) => this.resolve(slice.color)),
              // 2px de separación con el color de la superficie
              borderColor: surface,
              borderWidth: 2,
              hoverOffset: 6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend,
            tooltip: {
              callbacks: {
                label: (ctx) => {
                  const value = Number(ctx.raw);
                  const percent = total ? Math.round((value / total) * 100) : 0;
                  return ` ${ctx.label}: ${value}${unit} (${percent}%)`;
                },
              },
            },
          },
        },
      };
    }

    return {
      type: 'line',
      data: {
        labels: this.labels(),
        datasets: this.series().map((serie) => {
          const color = this.resolve(serie.color);
          return {
            label: serie.label,
            data: serie.data,
            borderColor: color,
            backgroundColor: this.alpha(color, 0.12),
            pointBackgroundColor: color,
            pointBorderColor: surface,
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2,
            tension: 0.3,
            fill: this.series().length === 1,
          };
        }),
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend,
          tooltip: { callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.raw}${unit}` } },
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: text, font } },
          y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { color: text, font, precision: 0 } },
        },
      },
    };
  }

  /** var(--x) → valor real del tema actual. */
  private resolve(color: string): string {
    const match = /^var\((--[^,)]+)(?:,\s*([^)]+))?\)$/.exec(color.trim());
    return match ? this.css(match[1], match[2] ?? '#94a3b8') : color;
  }

  private css(name: string, fallback: string): string {
    return getComputedStyle(this.host.nativeElement).getPropertyValue(name).trim() || fallback;
  }

  private alpha(color: string, amount: number): string {
    return /^#[0-9a-f]{6}$/i.test(color)
      ? color + Math.round(amount * 255).toString(16).padStart(2, '0')
      : color;
  }
}
