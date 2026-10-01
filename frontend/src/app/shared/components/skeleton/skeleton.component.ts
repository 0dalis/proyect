import { Component, computed, input } from '@angular/core';

export type SkeletonType = 'table' | 'cards' | 'chart' | 'lines' | 'detail' | 'list';

/**
 * Marcador de carga: formas grises casi transparentes con un brillo que las
 * recorre lentamente, como agua. Se muestra mientras llegan los datos.
 *
 *   @if (loading()) { <app-skeleton type="table" [rows]="6" /> } @else { ... }
 */
@Component({
  selector: 'app-skeleton',
  templateUrl: './skeleton.component.html',
  styleUrl: './skeleton.component.scss',
  host: { role: 'status', 'aria-live': 'polite', 'aria-busy': 'true' },
})
export class SkeletonComponent {
  readonly type = input<SkeletonType>('lines');
  readonly rows = input(5);
  readonly columns = input(5);
  /** Número de tarjetas (tipo cards). */
  readonly cards = input(4);

  protected readonly rowList = computed(() => Array.from({ length: this.rows() }, (_, i) => i));
  protected readonly columnList = computed(() =>
    Array.from({ length: this.columns() }, (_, i) => i),
  );
  protected readonly cardList = computed(() => Array.from({ length: this.cards() }, (_, i) => i));

  /** Anchos variados para que las líneas no se vean idénticas. */
  protected width(index: number, base = 60): string {
    return `${base + ((index * 37) % 35)}%`;
  }
}
