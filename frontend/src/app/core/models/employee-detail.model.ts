import { Device, Metrics, VacationBalance } from './attendance.model';
import { Employee } from './employee.model';
import { RoleName } from './user.model';

export interface RemoteWorkPeriod {
  id: number;
  starts_on: string;
  ends_on: string | null;
  weekdays: number[] | null;
  reason: string | null;
}

export interface EmployeeDetail extends Employee {
  vacation: VacationBalance;
  badge_issued_at: string | null;
  badge_expires_on: string | null;
  /** Foto del empleado (ficha y credencial); null si no hay. */
  photo_url: string | null;
  remote_work_periods: RemoteWorkPeriod[];
  managed_areas: { id: number; name: string }[];
  devices: Device[];
  user: EmployeeUser | null;
}

export type StatsPeriod = 'week' | 'month' | 'year';

export interface StatsBucket {
  key: string;
  label: string;
  on_time: number;
  late: number;
  absent: number;
  excused: number;
  minutes_late: number;
}

export interface EmployeeDay {
  date: string;
  status: 'on_time' | 'late' | 'absent' | 'excused' | 'holiday' | 'rest' | 'pending' | 'not_employed';
  justified: boolean;
  /** Si el día está cubierto por una solicitud aprobada, cuál es. */
  excused_type: 'vacation' | 'leave' | 'justification' | null;
  /** Hora local de la oficina (HH:mm). */
  check_in: string | null;
  check_out: string | null;
  minutes_late: number;
  overtime_minutes: number;
  /** Nombre del festivo, si lo es. */
  holiday: string | null;
}

export interface EmployeeStats {
  period: { type: StatsPeriod; from: string; to: string };
  buckets: StatsBucket[];
  metrics: Metrics;
  days: EmployeeDay[];
}

/** Usuario de la app de un empleado (interruptor "Acceso a la app"). */
export interface EmployeeUser {
  id: number;
  email: string;
  role: RoleName;
  role_label: string;
  app_access: boolean;
  web_access: boolean;
  blocked_at: string | null;
  last_login_at: string | null;
  /** Aún no entra por primera vez con su contraseña temporal. */
  must_change_password: boolean;
}
