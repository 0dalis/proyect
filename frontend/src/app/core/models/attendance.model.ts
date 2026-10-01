import { Office } from './organization.model';

export interface AttendanceRecord {
  id: number;
  employee_id: number;
  work_date: string;
  type: 'check_in' | 'check_out';
  recorded_at: string;
  channel: string;
  status: 'on_time' | 'late' | 'absent' | 'early_leave';
  minutes_late: number;
  minutes_early: number;
  overtime_minutes?: number;
  latitude: number | null;
  longitude: number | null;
  accuracy_meters: number | null;
  distance_meters: number | null;
  geofence_skipped: boolean;
  is_justified: boolean;
  employee?: { id: number; first_name: string; last_name: string; employee_code: string };
  office?: Pick<Office, 'id' | 'name' | 'latitude' | 'longitude' | 'geofence_radius' | 'timezone'>;
}

export interface DashboardData {
  today: {
    day: string;
    on_time: number;
    late: number;
    absent: number;
    active_employees: number;
    checked_in: number;
  };
  /** Cómo va el día de cada empleado, en la hora de su oficina. */
  team: TeamToday;
  series: { day: string; on_time: number; late: number; absent: number }[];
  month: {
    worked_days: number;
    scheduled_days: number;
    lates: number;
    absences: number;
    excused_days: number;
    overtime_hours: number;
    attendance_rate: number | null;
    top_lates: { id: number; public_id: string; name: string; lates: number; minutes_late: number }[];
  };
  /** % de entradas a tiempo en 30 días y en los 30 anteriores. */
  punctuality: { current: number | null; previous: number | null };
  upcoming_vacations: {
    id: number;
    name: string;
    starts_on: string;
    ends_on: string;
    ongoing: boolean;
  }[];
  pending_requests: number;
  pending_by_type: Partial<Record<string, number>>;
  pending_devices: number;
}

export type TeamState =
  | 'on_time'
  | 'late'
  | 'missing'
  | 'vacation'
  | 'leave'
  | 'upcoming'
  | 'rest'
  | 'holiday'
  | 'no_shift';

export interface TeamPerson {
  id: number;
  name: string;
  office: string;
  shift: string;
  area: string | null;
  state: TeamState;
  starts_at: string | null;
  check_in: string | null;
  minutes_late: number;
  until: string | null;
}

export interface TeamGroup {
  name: string;
  total: number;
  scheduled: number;
  registered: number;
  counts: Record<TeamState, number>;
}

export interface TeamToday {
  total: number;
  /** Les tocaba trabajar hoy (sin descansos, festivos ni empleados sin turno). */
  scheduled: number;
  /** Checaron entrada (a tiempo o con retardo). */
  registered: number;
  in_office_now: number;
  counts: Record<TeamState, number>;
  labels: Record<TeamState, string>;
  /** Vacíos si la empresa tiene una sola oficina o un solo turno. */
  by_office: TeamGroup[];
  by_shift: TeamGroup[];
  missing: TeamPerson[];
  late: TeamPerson[];
  away: TeamPerson[];
}

export type Metrics = Record<
  | 'scheduled_days'
  | 'worked_days'
  | 'on_time'
  | 'lates'
  | 'unjustified_lates'
  | 'absences'
  | 'unjustified_absences'
  | 'early_leaves'
  | 'minutes_late'
  | 'excused_days'
  | 'holidays'
  | 'holidays_worked'
  | 'overtime_minutes',
  number
>;

/** Vacaciones del año de servicio (art. 76 LFT). */
export interface VacationBalance {
  years_of_service: number;
  entitled: number;
  used: number;
  /** Apartados por solicitudes pendientes de aprobar. */
  pending: number;
  available: number;
  period_from: string | null;
  period_to: string | null;
  next_entitlement_on: string | null;
  hired_on: string | null;
}

export interface MySummary {
  period: { from: string; to: string };
  metrics: Metrics | null;
  vacation?: VacationBalance;
  timezone?: string | null;
  shift: {
    name: string;
    starts_at: string;
    ends_at: string;
    weekdays: number[];
    tolerance_minutes: number;
  };
  office: string | null;
  area: string | null;
}

export interface ReportRow {
  employee: {
    id: number;
    employee_code: string;
    name: string;
    area: string | null;
    area_color: string | null;
  };
  metrics: Metrics;
}

export interface Device {
  id: number;
  employee_id: number;
  device_identifier: string;
  name: string | null;
  platform: string | null;
  approved_at: string | null;
  revoked_at: string | null;
  last_used_at: string | null;
  employee?: { id: number; first_name: string; last_name: string };
}
