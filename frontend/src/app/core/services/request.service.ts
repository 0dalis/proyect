import { inject, Injectable } from '@angular/core';
import { EmployeeRequest, Paginated } from '../models';
import { ApiService, QueryParams } from './api.service';

export interface NewRequestPayload {
  type: string;
  starts_on: string;
  ends_on: string | null;
  expected_time: string | null;
  reason: string;
}

@Injectable({ providedIn: 'root' })
export class RequestService {
  private readonly api = inject(ApiService);

  list(params: QueryParams = {}): Promise<Paginated<EmployeeRequest>> {
    return this.api.get<Paginated<EmployeeRequest>>('requests', { per_page: 100, ...params });
  }

  create(payload: NewRequestPayload): Promise<EmployeeRequest> {
    return this.api.post<EmployeeRequest>('requests', payload);
  }

  review(id: number, decision: 'approved' | 'rejected', notes?: string): Promise<EmployeeRequest> {
    return this.api.post<EmployeeRequest>(`requests/${id}/review`, { decision, notes });
  }
}
