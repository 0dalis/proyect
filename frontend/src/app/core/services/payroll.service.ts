import { inject, Injectable } from '@angular/core';
import { Period } from '../../shared/utils/period';
import { BonusRule, PayrollPeriod, PayrollRow, PayrollTotals } from '../models';
import { ApiService } from './api.service';

export interface PayrollResponse {
  rows: PayrollRow[];
  totals: PayrollTotals;
  bonuses_enabled: boolean;
  /** Periodos cerrados que se cruzan con el rango consultado. */
  closed_periods: Pick<PayrollPeriod, 'id' | 'name' | 'starts_on' | 'ends_on'>[];
}

export interface BonusRuleCatalog {
  rules: BonusRule[];
  metrics: Record<string, string>;
  operators: string[];
}

/**
 * Pre-nómina y reglas de bonos.
 */
@Injectable({ providedIn: 'root' })
export class PayrollService {
  private readonly api = inject(ApiService);

  calculate(period: Period): Promise<PayrollResponse> {
    return this.api.get<PayrollResponse>('payroll', { ...period });
  }

  download(period: Period): Promise<void> {
    return this.api.download('payroll', `prenomina-${period.from}-${period.to}.csv`, {
      ...period,
      format: 'csv',
    });
  }

  periods(): Promise<PayrollPeriod[]> {
    return this.api.get<PayrollPeriod[]>('payroll/periods');
  }

  /** Guarda el cálculo y bloquea esas fechas. */
  closePeriod(period: Period, name?: string): Promise<PayrollPeriod> {
    return this.api.post<PayrollPeriod>('payroll/periods', { ...period, name: name || null });
  }

  closedPeriod(id: number): Promise<PayrollPeriod> {
    return this.api.get<PayrollPeriod>(`payroll/periods/${id}`);
  }

  downloadClosed(period: PayrollPeriod): Promise<void> {
    return this.api.download(
      `payroll/periods/${period.id}`,
      `prenomina-${period.starts_on.slice(0, 10)}-${period.ends_on.slice(0, 10)}-cerrada.csv`,
      { format: 'csv' },
    );
  }

  /** Solo el dueño. */
  reopen(id: number): Promise<{ message: string }> {
    return this.api.delete(`payroll/periods/${id}`);
  }

  bonusRules(): Promise<BonusRuleCatalog> {
    return this.api.get<BonusRuleCatalog>('bonus-rules');
  }

  saveBonusRule(rule: Record<string, unknown> & { id?: number }): Promise<BonusRule> {
    return rule.id
      ? this.api.put<BonusRule>(`bonus-rules/${rule.id}`, rule)
      : this.api.post<BonusRule>('bonus-rules', rule);
  }

  deleteBonusRule(id: number): Promise<unknown> {
    return this.api.delete(`bonus-rules/${id}`);
  }
}
