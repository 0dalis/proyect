import { inject, Injectable } from '@angular/core';
import { Kiosk } from '../models';
import { ApiService } from './api.service';

/**
 * Alta de kioskos desde el panel (no confundir con la pantalla del kiosko).
 */
@Injectable({ providedIn: 'root' })
export class KioskService {
  private readonly api = inject(ApiService);

  list(): Promise<Kiosk[]> {
    return this.api.get<Kiosk[]>('kiosks');
  }

  create(name: string, officeId: number): Promise<Kiosk> {
    return this.api.post<Kiosk>('kiosks', { name, office_id: officeId });
  }

  setActive(id: number, isActive: boolean): Promise<Kiosk> {
    return this.api.put<Kiosk>(`kiosks/${id}`, { is_active: isActive });
  }

  regenerateToken(id: number): Promise<{ token: string }> {
    return this.api.post(`kiosks/${id}/token`);
  }
}
