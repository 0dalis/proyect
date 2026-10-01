import { CurrencyPipe } from '@angular/common';
import { Component, computed, effect, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { PayrollPeriod, PayrollRow, PayrollTotals } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { DialogService } from '../../../core/services/dialog.service';
import { PayrollResponse, PayrollService } from '../../../core/services/payroll.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { PeriodPickerComponent } from '../../../shared/components/period-picker/period-picker.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';
import { Period, periodPresets } from '../../../shared/utils/period';

/**
 * Pre-nómina: se calcula al momento para el periodo elegido. Al cerrarlo, el
 * cálculo queda guardado y esas fechas ya no se modifican (checadas,
 * justificaciones, solicitudes) hasta que el dueño lo reabra.
 */
@Component({
  selector: 'app-payroll',
  imports: [SkeletonComponent, CurrencyPipe, RouterLink, PeriodPickerComponent, PageHeaderComponent],
  templateUrl: './payroll.component.html',
  styleUrl: './payroll.component.scss',
})
export class PayrollComponent {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly toast = inject(ToastService);
  private readonly dialog = inject(DialogService);
  private readonly payrollService = inject(PayrollService);
  protected readonly auth = inject(AuthService);

  protected readonly periods: Record<string, string> = {
    daily: 'diario',
    weekly: 'semanal',
    biweekly: 'quincenal',
    monthly: 'mensual',
  };
  protected readonly period = signal<Period>(periodPresets()[0].period);
  protected readonly data = signal<PayrollResponse | null>(null);
  protected readonly closedPeriods = signal<PayrollPeriod[]>([]);
  /** Pre-nómina cerrada que se está consultando (en lugar del cálculo al momento). */
  protected readonly viewing = signal<PayrollPeriod | null>(null);
  protected readonly closing = signal(false);

  protected readonly rows = computed<PayrollRow[]>(() => this.viewing()?.rows ?? this.data()?.rows ?? []);
  protected readonly totals = computed<PayrollTotals | null>(
    () => this.viewing()?.totals ?? this.data()?.totals ?? null,
  );
  /** El periodo elegido ya terminó y no se cruza con uno cerrado. */
  protected readonly canClose = computed(() => {
    const data = this.data();
    return !!data && !data.closed_periods.length && this.period().to <= this.today();
  });

  constructor() {
    effect(() => {
      this.period();
      this.viewing.set(null);
      this.load();
    });
    void this.loadPeriods();
  }

  protected async load(): Promise<void> {
    try {
      this.data.set(await this.payrollService.calculate(this.period()));
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async exportCsv(): Promise<void> {
    try {
      const viewing = this.viewing();
      await (viewing ? this.payrollService.downloadClosed(viewing) : this.payrollService.download(this.period()));
      this.toast.success('Ábrelo con Excel o Google Sheets.', {
        title: 'Pre-nómina descargada',
        icon: 'file-earmark-spreadsheet',
      });
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async close(): Promise<void> {
    const period = this.period();
    const name = await this.dialog.prompt({
      title: 'Cerrar pre-nómina',
      text: `Del ${this.dateLabel(period.from)} al ${this.dateLabel(period.to)}. El cálculo quedará guardado y ya no se podrán cambiar checadas, justificaciones ni solicitudes de esas fechas. Solo el dueño puede reabrirla.`,
      inputLabel: 'Nombre (opcional)',
      placeholder: 'Ej. Primera quincena de septiembre',
      confirmText: 'Cerrar periodo',
    });
    if (name === null) {
      return;
    }
    this.closing.set(true);
    try {
      const closed = await this.payrollService.closePeriod(period, name);
      this.toast.success(`${closed.name} quedó guardada.`, { title: 'Pre-nómina cerrada', icon: 'lock' });
      await Promise.all([this.load(), this.loadPeriods()]);
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se cerró' });
    } finally {
      this.closing.set(false);
    }
  }

  protected async view(period: Pick<PayrollPeriod, 'id'>): Promise<void> {
    try {
      this.viewing.set(await this.payrollService.closedPeriod(period.id));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async reopen(period: PayrollPeriod): Promise<void> {
    const confirmed = await this.dialog.confirm({
      title: `¿Reabrir "${period.name}"?`,
      text: 'Se borrará el cálculo guardado y esas fechas se podrán modificar otra vez.',
      confirmText: 'Reabrir',
      variant: 'danger',
    });
    if (!confirmed) {
      return;
    }
    try {
      const { message } = await this.payrollService.reopen(period.id);
      this.toast.info(message, { title: 'Pre-nómina reabierta' });
      if (this.viewing()?.id === period.id) {
        this.viewing.set(null);
      }
      await Promise.all([this.load(), this.loadPeriods()]);
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  /** "2026-09-15" → "15 sep 2026" (sin desfase por zona horaria). */
  protected dateLabel(value: string): string {
    const [y, m, d] = value.slice(0, 10).split('-').map(Number);
    return new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(
      new Date(Date.UTC(y, m - 1, d)),
    );
  }

  private async loadPeriods(): Promise<void> {
    try {
      this.closedPeriods.set(await this.payrollService.periods());
    } catch {
      // La lista de cerrados no impide calcular
    }
  }

  private today(): string {
    const now = new Date();
    return new Date(now.getTime() - now.getTimezoneOffset() * 60_000).toISOString().slice(0, 10);
  }
}
