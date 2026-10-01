export interface Employee {
  id: number;
  /** Identificador cifrado para las URLs; el id numérico no se expone. */
  public_id: string;
  employee_code: string;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
  position: string | null;
  office_id: number;
  shift_id: number;
  area_id: number;
  status: 'active' | 'inactive' | 'terminated';
  employment_type: 'permanent' | 'temporary';
  work_mode: 'onsite' | 'remote';
  hired_on: string | null;
  contract_ends_on: string | null;
  /** Alta en el sistema; el calendario de asistencia arranca en este mes. */
  created_at?: string | null;
  salary?: string | null;
  salary_period?: 'daily' | 'weekly' | 'biweekly' | 'monthly' | null;
  user_id?: number | null;
  /** Fuera del límite del plan: se ve, pero no checa ni se puede modificar. */
  locked_by_plan?: boolean;
  /** Carga masiva: le falta oficina, turno, área o tipo (no puede checar). */
  needs_setup?: boolean;
  office?: { id: number; name: string };
  shift?: { id: number; name: string; starts_at: string; ends_at: string };
  area?: { id: number; name: string; color: string | null };
}
