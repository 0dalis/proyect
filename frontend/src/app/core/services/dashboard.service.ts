import { inject, Injectable } from '@angular/core';
import { DashboardData } from '../models';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private readonly api = inject(ApiService);

  get(): Promise<DashboardData> {
    return this.api.get<DashboardData>('dashboard');
  }
}
