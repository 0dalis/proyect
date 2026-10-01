export interface Kiosk {
  id: number;
  name: string;
  office_id: number;
  is_active: boolean;
  last_seen_at: string | null;
  office?: { id: number; name: string };
  token?: string;
}

export interface KioskInfo {
  kiosk: { id: number; name: string };
  office: { id: number; name: string; timezone: string };
  company: { id: number; name: string };
}

export interface PunchResult {
  type: 'check_in' | 'check_out';
  status: string;
  minutes_late: number;
  recorded_at: string;
  /** Hora en la oficina del kiosko (HH:mm). */
  local_time: string;
  timezone: string;
  duplicate: boolean;
  employee: { name: string; employee_code: string };
}
