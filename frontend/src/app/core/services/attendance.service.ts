import { inject, Injectable } from '@angular/core';
import { AttendanceRecord, Paginated } from '../models';
import { ApiService, QueryParams } from './api.service';

export interface PunchSummary {
  id: number;
  type_label: string;
  status_label: string;
  is_justified: boolean;
}

@Injectable({ providedIn: 'root' })
export class AttendanceService {
  private readonly api = inject(ApiService);

  list(params: QueryParams = {}): Promise<Paginated<AttendanceRecord>> {
    return this.api.get<Paginated<AttendanceRecord>>('attendance', { per_page: 200, ...params });
  }

  registerManual(employeeId: number, recordedAt: string): Promise<PunchSummary> {
    return this.api.post('attendance/manual', { employee_id: employeeId, recorded_at: recordedAt });
  }

  setJustified(id: number, isJustified: boolean): Promise<PunchSummary> {
    return this.api.patch(`attendance/${id}/justify`, { is_justified: isJustified });
  }
}
