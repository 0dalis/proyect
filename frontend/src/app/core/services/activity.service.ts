import { inject, Injectable } from '@angular/core';
import { ActivityPage } from '../models';
import { ApiService, QueryParams } from './api.service';

export interface ActivityFilters extends QueryParams {
  from?: string;
  to?: string;
  action?: string;
  user_id?: number | null;
  employee_id?: number | null;
  scope?: 'all' | 'general' | 'employees';
  search?: string;
  page?: number;
}

/**
 * Bitácora de la empresa: quién hizo qué, sobre qué y cuándo.
 */
@Injectable({ providedIn: 'root' })
export class ActivityService {
  private readonly api = inject(ApiService);

  list(filters: ActivityFilters = {}): Promise<ActivityPage> {
    return this.api.get<ActivityPage>('activity', { per_page: 50, ...filters });
  }

  download(filters: ActivityFilters = {}): Promise<void> {
    return this.api.download('activity', `bitacora-${new Date().toLocaleDateString('en-CA')}.csv`, {
      ...filters,
      format: 'csv',
    });
  }
}
