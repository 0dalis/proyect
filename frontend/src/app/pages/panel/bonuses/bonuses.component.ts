import { CurrencyPipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { BonusCondition, BonusRule, Employee } from '../../../core/models';
import { EmployeeService } from '../../../core/services/employee.service';
import { PayrollService } from '../../../core/services/payroll.service';
import { errorMessage } from '../../../core/utils/error-message';
import { ModalComponent } from '../../../shared/components/modal/modal.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { ToastService } from '../../../core/services/toast.service';
import { DialogService } from '../../../core/services/dialog.service';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

interface RuleDraft {
  id?: number;
  name: string;
  period: BonusRule['period'];
  amount_type: BonusRule['amount_type'];
  amount: number;
  conditions: BonusCondition[];
  scope: 'all' | 'some';
  employee_ids: number[];
  is_active: boolean;
}

const OPERATOR_LABELS: Record<string, string> = {
  '<': 'menor que',
  '<=': 'como máximo',
  '=': 'igual a',
  '>=': 'al menos',
  '>': 'mayor que',
};

@Component({
  selector: 'app-bonuses',
  imports: [SkeletonComponent, FormsModule, CurrencyPipe, ModalComponent, PageHeaderComponent],
  templateUrl: './bonuses.component.html',
  styleUrl: './bonuses.component.scss',
})
export class BonusesComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly dialog = inject(DialogService);
  private readonly toast = inject(ToastService);
  private readonly payrollService = inject(PayrollService);
  private readonly employeeService = inject(EmployeeService);

  protected readonly periods: Record<string, string> = {
    weekly: 'por semana',
    biweekly: 'por quincena',
    monthly: 'por mes',
  };
  protected readonly operatorLabels = OPERATOR_LABELS;
  protected readonly rules = signal<BonusRule[]>([]);
  protected readonly metrics = signal<Record<string, string>>({});
  protected readonly metricOptions = signal<{ key: string; label: string }[]>([]);
  protected readonly employees = signal<Employee[]>([]);
  protected readonly draft = signal<RuleDraft | null>(null);
  protected readonly modalError = signal<string | null>(null);
  protected operators: string[] = [];

  async ngOnInit(): Promise<void> {
    await this.load();
    this.employees.set(await this.employeeService.active());
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    try {
      const data = await this.payrollService.bonusRules();
      this.rules.set(data.rules);
      this.metrics.set(data.metrics);
      this.metricOptions.set(Object.entries(data.metrics).map(([key, label]) => ({ key, label })));
      this.operators = data.operators;
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected describe(condition: BonusCondition): string {
    return `${(this.metrics()[condition.metric] ?? condition.metric).toLowerCase()} ${OPERATOR_LABELS[condition.operator]} ${condition.value}`;
  }

  protected open(rule?: BonusRule): void {
    this.modalError.set(null);
    this.draft.set({
      id: rule?.id,
      name: rule?.name ?? '',
      period: rule?.period ?? 'biweekly',
      amount_type: rule?.amount_type ?? 'fixed',
      amount: rule ? Number(rule.amount) : 500,
      conditions: rule
        ? rule.conditions.map((c) => ({ ...c }))
        : [{ metric: 'unjustified_lates', operator: '<', value: 3 }],
      scope: rule?.employee_ids?.length ? 'some' : 'all',
      employee_ids: rule?.employee_ids ?? [],
      is_active: rule?.is_active ?? true,
    });
  }

  protected async save(rule: RuleDraft): Promise<void> {
    const payload = { ...rule, employee_ids: rule.scope === 'some' ? rule.employee_ids : null };
    try {
      await this.payrollService.saveBonusRule(payload);
      this.draft.set(null);
      this.toast.success(rule.name, {
        title: rule.id ? 'Regla actualizada' : 'Regla creada',
        icon: 'award-fill',
      });
      await this.load();
    } catch (error) {
      this.modalError.set(errorMessage(error));
    }
  }

  protected async remove(rule: BonusRule): Promise<void> {
    const confirmed = await this.dialog.confirm({
      title: `¿Eliminar "${rule.name}"?`,
      text: 'Dejará de calcularse en la pre-nómina. Si solo quieres pausarla, edítala y desactívala.',
      confirmText: 'Eliminar regla',
      variant: 'danger',
    });
    if (!confirmed) {
      return;
    }
    try {
      await this.payrollService.deleteBonusRule(rule.id);
      this.toast.success(rule.name, { title: 'Regla eliminada', icon: 'trash3' });
      await this.load();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }
}
