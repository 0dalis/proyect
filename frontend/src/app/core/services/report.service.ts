import { inject, Injectable } from '@angular/core';
import { Period } from '../../shared/utils/period';
import { ReportRow } from '../models';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class ReportService {
  private readonly api = inject(ApiService);

  async attendance(period: Period, areaId: number | null): Promise<ReportRow[]> {
    return (
      await this.api.get<{ rows: ReportRow[] }>('reports/attendance', {
        ...period,
        area_id: areaId,
      })
    ).rows;
  }

  downloadAttendance(period: Period, areaId: number | null): Promise<void> {
    return this.api.download('reports/attendance', `asistencia-${period.from}-${period.to}.csv`, {
      ...period,
      area_id: areaId,
      format: 'csv',
    });
  }
}
