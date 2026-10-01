import { Metrics } from './attendance.model';

export interface PayrollRow {
  employee: {
    id: number;
    employee_code: string;
    name: string;
    area: string | null;
    salary: number | null;
    salary_period: string | null;
  };
  metrics: Metrics;
  base: number;
  deductions: number;
  /** Arts. 67 y 68 LFT: 9 h a la semana al doble, el resto al triple. */
  overtime: { double_hours: number; triple_hours: number; amount: number };
  /** Festivo trabajado: doble adicional (art. 75 LFT). */
  holiday_pay: number;
  bonuses: { rule_id: number; name: string; amount: number }[];
  bonus_total: number;
  total: number;
}

export interface BonusCondition {
  metric: string;
  operator: string;
  value: number;
}

export interface BonusRule {
  id: number;
  name: string;
  period: 'weekly' | 'biweekly' | 'monthly';
  amount_type: 'fixed' | 'percent';
  amount: string;
  conditions: BonusCondition[];
  employee_ids: number[] | null;
  is_active: boolean;
}

export interface PayrollTotals {
  employees?: number;
  base: number;
  deductions: number;
  overtime: number;
  holidays: number;
  bonuses: number;
  total: number;
}

/** Pre-nómina cerrada: el cálculo quedó guardado y sus fechas no se modifican. */
export interface PayrollPeriod {
  id: number;
  name: string;
  starts_on: string;
  ends_on: string;
  closed_by_name: string | null;
  closed_at: string;
  totals: PayrollTotals;
  items_count?: number;
  rows?: PayrollRow[];
}
