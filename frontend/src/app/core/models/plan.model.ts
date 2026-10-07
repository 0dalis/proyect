import type { CurrentUser } from './user.model';

export interface Plan {
  name: string;
  slug: string;
  description: string | null;
  monthly_price: string;
  included_employees: number;
  included_offices: number;
  employee_block_size: number;
  employee_block_price: string;
  extra_office_price: string;
  features: string[] | null;
  includes_payroll: boolean;
  is_free: boolean;
  /** 11 mensualidades: un mes de regalo al pagar el año. */
  yearly_price: number;
  database_tier: 'basic' | 'plus' | 'premium';
  database_label: string;
  /** 0 en Free (no tiene prueba). */
  trial_days: number;
}

export type BillingInterval = 'month' | 'year';

/** Lo que necesita el modal de bienvenida o la pantalla de cambio de plan. */
export interface PlanOptions {
  company_name: string;
  accepted: boolean;
  legal_version: string;
  /** demo = sin Stripe configurado (solo desarrollo). */
  billing_mode: 'stripe' | 'demo';
  stripe_key: string | null;
  current_plan: string;
  plans: Plan[];
}

export interface ChoosePlanResult {
  status: 'trial' | 'active';
  trial_ends_at: string | null;
  /** Primer cobro: un día antes de que termine la prueba. */
  charge_on: string | null;
  plan: Plan;
  interval: BillingInterval | null;
  user: CurrentUser;
}

export interface Subscription {
  plan: {
    name: string;
    slug: string;
    monthly_price: string;
    yearly_price: number;
    is_free: boolean;
    includes_payroll: boolean;
    employee_block_size: number;
    employee_block_price: string;
    extra_office_price: string;
  };
  billing_interval: BillingInterval | null;
  past_due_since: string | null;
  database_tier: string;
  status: string;
  status_label: string;
  trial_ends_at: string | null;
  extras: { extra_employee_blocks: number; extra_offices: number };
  usage: Record<'employees' | 'offices', { used: number; limit: number; locked?: number }>;
  /** Empleados con app activa; su tope es el límite de empleados del plan. */
  app_users: number;
  monthly_price: number;
  payment_method: string | null;
  available_plans: {
    name: string;
    slug: string;
    monthly_price: string;
    included_employees: number;
    included_offices: number;
  }[];
}
