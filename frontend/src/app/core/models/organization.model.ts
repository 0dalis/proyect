export interface Office {
  id: number;
  name: string;
  address: string | null;
  latitude: number | null;
  longitude: number | null;
  geofence_radius: number;
  timezone: string;
  is_default: boolean;
  employees_count?: number;
  shifts?: Shift[];
}

export interface Shift {
  id: number;
  office_id: number;
  name: string;
  starts_at: string;
  ends_at: string;
  /** Comida o descanso sin goce, se descuenta de las horas trabajadas. */
  break_minutes: number;
  weekdays: number[];
  /** Horario distinto en algunos días activos (clave: día ISO, 5 = viernes). */
  day_schedules: Record<string, DaySchedule> | null;
  tolerance_minutes: number;
  absence_after_minutes: number;
  is_default: boolean;
  office?: { id: number; name: string };
}

export interface Area {
  id: number;
  name: string;
  color: string | null;
  description: string | null;
  employees_count?: number;
  managers?: { id: number; first_name: string; last_name: string }[];
}

/** Horario especial de un día del turno (ej. viernes de 9 a 17). */
export interface DaySchedule {
  starts_at: string;
  ends_at: string;
  /** null = la misma comida del turno. */
  break_minutes: number | null;
}

/** Día festivo de la empresa. */
export interface Holiday {
  id: number;
  date: string;
  name: string;
  /** Descanso obligatorio de la LFT (art. 74). */
  is_official: boolean;
}
