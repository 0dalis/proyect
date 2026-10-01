import { inject, Injectable } from '@angular/core';
import { Area, Holiday, Office, Shift } from '../models';
import { ApiService } from './api.service';

/**
 * Oficinas (con geocerca y zona horaria), turnos, áreas y días festivos.
 */
@Injectable({ providedIn: 'root' })
export class OrganizationService {
  private readonly api = inject(ApiService);

  holidays(year: number): Promise<Holiday[]> {
    return this.api.get<Holiday[]>('holidays', { year });
  }

  addHoliday(date: string, name: string): Promise<Holiday> {
    return this.api.post<Holiday>('holidays', { date, name });
  }

  /** Agrega los descansos obligatorios de la LFT que falten en el año. */
  loadOfficialHolidays(year: number): Promise<{ added: number; holidays: Holiday[] }> {
    return this.api.post('holidays/official', { year });
  }

  deleteHoliday(id: number): Promise<void> {
    return this.api.delete(`holidays/${id}`);
  }

  offices(): Promise<Office[]> {
    return this.api.get<Office[]>('offices');
  }

  saveOffice(office: Partial<Office>): Promise<Office> {
    return office.id
      ? this.api.put<Office>(`offices/${office.id}`, office)
      : this.api.post<Office>('offices', office);
  }

  shifts(officeId?: number): Promise<Shift[]> {
    return this.api.get<Shift[]>('shifts', { office_id: officeId });
  }

  saveShift(shift: Partial<Shift>): Promise<Shift> {
    return shift.id
      ? this.api.put<Shift>(`shifts/${shift.id}`, shift)
      : this.api.post<Shift>('shifts', shift);
  }

  areas(): Promise<Area[]> {
    return this.api.get<Area[]>('areas');
  }

  saveArea(area: { id?: number; name: string; color: string }): Promise<Area> {
    return area.id
      ? this.api.put<Area>(`areas/${area.id}`, area)
      : this.api.post<Area>('areas', area);
  }

  syncAreaManagers(areaId: number, employeeIds: number[]): Promise<Area> {
    return this.api.put<Area>(`areas/${areaId}/managers`, { employee_ids: employeeIds });
  }
}
