import { CurrentUser } from '../models';

/**
 * Usuario de prueba; se sobrescriben solo los campos que importan en cada caso.
 */
export function fakeUser(overrides: Partial<CurrentUser> = {}): CurrentUser {
  return {
    id: 1,
    name: 'Dueño Demo',
    email: 'owner@demo.test',
    role: 'owner',
    role_label: 'Dueño',
    must_change_password: false,
    can_rate: true,
    permissions: ['employees.view', 'reports.view', 'payroll.manage', 'organization.manage'],
    avatar_url: null,
    employee: null,
    company: {
      id: 1,
      name: 'Almacenes Demo',
      logo_url: null,
      code: 'DEMO2026',
      timezone: 'America/Mexico_City',
      status: 'trial',
      status_label: 'En prueba',
      plan: 'Plus',
      plan_slug: 'plus',
      billing_interval: 'month',
      onboarding: false,
      trial_ends_at: null,
      past_due_since: null,
      includes_payroll: true,
      employees_can_use_web: true,
      payroll_enabled: true,
      bonuses_enabled: false,
      limits: { employees: 25, offices: 1 },
    },
    ...overrides,
  };
}
