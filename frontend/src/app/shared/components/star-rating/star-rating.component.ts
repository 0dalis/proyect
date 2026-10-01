import { booleanAttribute, Component, computed, input, model, signal } from '@angular/core';

export type StarState = 'full' | 'half' | 'empty';

/** Estado de cada una de las 5 estrellas para una calificación (0.5 en 0.5). */
export function starStates(score: number): StarState[] {
  return [1, 2, 3, 4, 5].map((star) =>
    score >= star ? 'full' : score >= star - 0.5 ? 'half' : 'empty',
  );
}

/** Redondea a la media estrella más cercana, entre `min` y 5. */
export function clampScore(value: number, min = 1): number {
  return Math.min(5, Math.max(min, Math.round(value * 2) / 2));
}

export const SCORE_LABELS: Record<number, string> = {
  1: 'Muy mala',
  1.5: 'Muy mala',
  2: 'Mala',
  2.5: 'Regular',
  3: 'Regular',
  3.5: 'Buena',
  4: 'Buena',
  4.5: 'Muy buena',
  5: 'Excelente',
};

/**
 * Calificación de 1 a 5 estrellas con medias estrellas. La mitad izquierda de
 * cada estrella vale media. Con teclado: flechas (±0.5), Inicio y Fin.
 *
 *   <app-star-rating [(value)]="score" />
 *   <app-star-rating [value]="4.5" readonly size="sm" />
 */
@Component({
  selector: 'app-star-rating',
  templateUrl: './star-rating.component.html',
  styleUrl: './star-rating.component.scss',
  host: { '[class.readonly]': 'readonly()', '[attr.data-size]': 'size()' },
})
export class StarRatingComponent {
  readonly value = model(0);
  readonly readonly = input(false, { transform: booleanAttribute });
  readonly size = input<'sm' | 'md' | 'lg'>('md');
  readonly label = input('Calificación');

  protected readonly hovered = signal(0);
  protected readonly shown = computed(() => this.hovered() || this.value());
  protected readonly states = computed(() => starStates(this.shown()));
  protected readonly text = computed(() => SCORE_LABELS[this.shown()] ?? '');

  /** Media estrella si el puntero está en la mitad izquierda. */
  protected scoreAt(event: MouseEvent, star: number): number {
    const target = event.currentTarget as HTMLElement;
    const { left, width } = target.getBoundingClientRect();
    return event.clientX - left < width / 2 ? star - 0.5 : star;
  }

  protected hover(event: MouseEvent, star: number): void {
    if (!this.readonly()) {
      this.hovered.set(clampScore(this.scoreAt(event, star)));
    }
  }

  protected pick(event: MouseEvent, star: number): void {
    if (!this.readonly()) {
      this.value.set(clampScore(this.scoreAt(event, star)));
    }
  }

  protected onKeydown(event: KeyboardEvent): void {
    if (this.readonly()) {
      return;
    }
    const current = this.value() || 0;
    const next: Record<string, number> = {
      ArrowRight: current + 0.5,
      ArrowUp: current + 0.5,
      ArrowLeft: current - 0.5,
      ArrowDown: current - 0.5,
      Home: 1,
      End: 5,
    };
    if (event.key in next) {
      event.preventDefault();
      this.value.set(clampScore(next[event.key]));
    }
  }
}
