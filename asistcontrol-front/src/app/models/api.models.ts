export interface Office {
  id: number;
  name: string;
  code?: string | null;
  latitude: number;
  longitude: number;
  radius_meters: number;
  timezone: string;
  country?: string | null;
  is_active: boolean;
  shifts?: Shift[];
}

export interface Area {
  id: number;
  name: string;
  is_active: boolean;
}

export interface Shift {
  id: number;
  office_id: number;
  name: string;
  start_time: string;
  end_time: string;
  cross_midnight: boolean;
  work_days?: number[];
  lunch_start?: string | null;
  lunch_end?: string | null;
  tolerance_minutes: number;
  early_leave_minutes: number;
  work_hours_expected?: number | null;
  is_active: boolean;
}

export interface VacationBalance {
  employee_id: number;
  employee_name?: string;
  employee_code?: string;
  year: number;
  days_entitled: number;
  days_used: number;
  days_available: number;
}

export interface Employee {
  id: number;
  employee_code: string;
  first_name: string;
  last_name: string;
  full_name?: string;
  office_id: number;
  area_id?: number | null;
  shift_id?: number | null;
  is_area_manager: boolean;
  is_active: boolean;
  position?: string | null;
  hired_at?: string | null;
  bank_name?: string | null;
  bank_account?: string | null;
  office?: Office;
  area?: Area | null;
  shift?: Shift | null;
  compensation?: EmployeeCompensation | null;
  concepts?: EmployeeConcept[];
}

export interface Credential {
  id: number;
  employee_id: number;
  orientation: 'horizontal' | 'vertical';
  design?: any;
  qr_token?: string | null;
  qr_payload?: string | null;
  photo_path?: string | null;
  photo_url?: string | null;
  pdf_path?: string | null;
  pdf_url?: string | null;
  download_enabled: boolean;
  issued_at?: string | null;
  last_printed_at?: string | null;
  print_count?: number;
}

export interface CredentialResponse {
  credential: Credential;
  employee: {
    id: number;
    employee_code: string;
    first_name: string;
    last_name: string;
    full_name?: string;
    position?: string | null;
    user_id?: number | null;
    office?: string | null;
    area?: string | null;
  };
  company: {
    id: number;
    name: string;
    code: string;
    logo_path?: string | null;
  };
}

export interface EmployeeCompensation {
  id?: number;
  employee_id?: number;
  salary_type: 'fixed' | 'hourly';
  base_salary: number;
  pay_frequency: 'weekly' | 'biweekly' | 'monthly';
  daily_hours: number;
  overtime_factor: number;
  overtime_cap_minutes?: number | null;
  currency: string;
}

export interface EmployeeConcept {
  id?: number;
  payroll_concept_id: number;
  amount_override?: number | null;
  is_active: boolean;
  concept?: PayrollConcept;
}

export interface PayrollConcept {
  id: number;
  name: string;
  type: 'bonus' | 'deduction';
  calculation: 'fixed' | 'percentage' | 'per_hour' | 'per_day';
  amount: number;
  is_recurring: boolean;
  is_active: boolean;
  description?: string | null;
}

export interface AttendanceRecord {
  id: number;
  type: 'check_in' | 'check_out' | 'lunch_start' | 'lunch_end';
  recorded_at: string;
  latitude?: number | null;
  longitude?: number | null;
  source?: string;
}

export interface Attendance {
  id: number;
  employee_id: number;
  office_id: number;
  shift_id?: number | null;
  date: string;
  status: 'present' | 'late' | 'absent' | 'justified';
  leave_type?: 'vacation' | 'permission' | 'sick' | 'holiday' | null;
  worked_minutes: number;
  late_minutes: number;
  early_minutes: number;
  overtime_minutes: number;
  source: string;
  employee?: Employee;
  office?: Office;
  shift?: Shift;
  records?: AttendanceRecord[];
}

export interface PayrollPeriod {
  id: number;
  name: string;
  frequency: 'weekly' | 'biweekly' | 'monthly';
  start_date: string;
  end_date: string;
  status: 'draft' | 'calculated' | 'closed';
  notes?: string | null;
  items_count?: number;
  calculated_at?: string | null;
  closed_at?: string | null;
  items?: PayrollItem[];
}

export interface PayrollItem {
  id: number;
  employee_id: number;
  worked_days: number;
  worked_minutes: number;
  overtime_minutes: number;
  late_count: number;
  absence_count: number;
  base_amount: number;
  overtime_amount: number;
  bonuses_amount: number;
  deductions_amount: number;
  gross_amount: number;
  net_amount: number;
  breakdown?: { concept: string; type: string; amount: number }[];
  employee?: Employee;
}

export interface PayrollSettings {
  currency: string;
  timezone: string;
  default_pay_frequency: 'weekly' | 'biweekly' | 'monthly';
  default_vacation_days?: number;
  attendance_bonus_enabled: boolean;
  attendance_bonus_amount: number;
  late_penalty_amount: number;
  absence_penalty_amount: number;
  overtime_enabled: boolean;
}

export interface WorkRequest {
  id: number;
  user_id: number;
  type: 'permission' | 'justification' | 'vacation';
  start_date: string;
  end_date?: string | null;
  start_time?: string | null;
  end_time?: string | null;
  reason?: string | null;
  status: 'pending' | 'approved' | 'rejected';
  is_paid: boolean;
  user?: { id: number; email: string; employee?: Employee };
  created_at?: string;
}

export interface AppNotification {
  id: number;
  title: string;
  message: string;
  target_type: 'all' | 'area' | 'user';
  priority: 'normal' | 'high' | 'urgent';
  is_active: boolean;
  sent_at?: string | null;
  scheduled_at?: string | null;
  reads_count?: number;
  area?: Area | null;
}

export type ExportFormat = 'csv' | 'xlsx' | 'pdf';

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
