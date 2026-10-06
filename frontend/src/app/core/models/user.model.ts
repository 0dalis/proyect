import type { BillingInterval } from './plan.model';

export type RoleName = 'owner' | 'admin' | 'manager' | 'employee';

export interface CurrentUser {
  id: number;
  name: string;
  email: string;
  role: RoleName;
  role_label: string;
  /** Contraseña temporal: antes de seguir debe cambiarla y crear su PIN. */
  must_change_password: boolean;
  /** Dueño y administradores pueden calificar AsistControl. */
  can_rate: boolean;
  permissions: string[];
  /** Foto de la cuenta (navbar y Mi perfil); null si aún no hay. */
  avatar_url: string | null;
  employee: {
    id: number;
    name: string;
    employee_code: string;
    area_id: number;
    office_id: number;
    shift_id: number;
    has_pin: boolean;
  } | null;
  company: {
    id: number;
    name: string;
    /** Logotipo en la marca del sidebar; null si no hay. */
    logo_url: string | null;
    /** Código de empresa: los empleados lo usan para entrar a la app. */
    code: string;
    /** Zona horaria de la empresa; cada oficina puede tener la suya. */
    timezone: string;
    status: string;
    status_label: string;
    plan: string;
    plan_slug: string;
    billing_interval: BillingInterval | null;
    /** Primera vez del dueño: solo ve el modal de bienvenida (términos, plan, tarjeta). */
    onboarding: boolean;
    trial_ends_at: string | null;
    /** Cobro fallido: a los 3 días pasa a Free. */
    past_due_since: string | null;
    includes_payroll: boolean;
    employees_can_use_web: boolean;
    payroll_enabled: boolean;
    bonuses_enabled: boolean;
    limits: { employees: number; offices: number };
  };
  /** Oficina para el clima: la del empleado o la primera de la empresa con coordenadas. */
  weather_location?: { latitude: number; longitude: number; place: string } | null;
}
