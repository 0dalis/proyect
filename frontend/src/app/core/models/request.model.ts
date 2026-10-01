export interface EmployeeRequest {
  id: number;
  employee_id: number;
  type: 'justification' | 'late_arrival' | 'early_departure' | 'vacation' | 'leave';
  starts_on: string;
  ends_on: string | null;
  expected_time: string | null;
  reason: string;
  status: 'pending' | 'approved' | 'rejected';
  review_notes: string | null;
  created_at: string;
  can_review: boolean;
  employee?: { id: number; first_name: string; last_name: string };
}
