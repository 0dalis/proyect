import { Component, computed, input } from '@angular/core';
import { VacationBalance } from '../../../core/models';

/**
 * Saldo de vacaciones del año de servicio (art. 76 LFT): días que le tocan,
 * usados, apartados por solicitudes pendientes y disponibles.
 */
@Component({
  selector: 'app-vacation-balance',
  templateUrl: './vacation-balance.component.html',
  styleUrl: './vacation-balance.component.scss',
})
export class VacationBalanceComponent {
  readonly balance = input.required<VacationBalance>();
  /** Versión compacta para dentro de formularios. */
  readonly compact = input(false);

  protected readonly usedPercent = computed(() => this.percent(this.balance().used));
  protected readonly pendingPercent = computed(() => this.percent(this.balance().pending));

  /** "2027-07-01" → "1 de julio de 2027" (sin desfase por zona horaria). */
  protected date(value: string | null): string {
    if (!value) {
      return '';
    }
    const [y, m, d] = value.slice(0, 10).split('-').map(Number);
    return new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(
      new Date(Date.UTC(y, m - 1, d)),
    );
  }

  private percent(days: number): number {
    const entitled = this.balance().entitled;
    return entitled ? Math.min(100, Math.round((days / entitled) * 100)) : 0;
  }
}
