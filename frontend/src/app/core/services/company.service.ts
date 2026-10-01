import { inject, Injectable } from '@angular/core';
import { Subscription } from '../models';
import { ApiService } from './api.service';

export interface CompanySettings {
  name: string;
  employees_can_use_web: boolean;
  payroll_enabled: boolean;
  bonuses_enabled: boolean;
}

@Injectable({ providedIn: 'root' })
export class CompanyService {
  private readonly api = inject(ApiService);

  updateSettings(settings: Partial<CompanySettings>): Promise<Partial<CompanySettings>> {
    return this.api.patch('company/settings', settings);
  }

  /** Logotipo de la marca del sidebar (solo el dueño lo sube). */
  uploadLogo(photo: File): Promise<{ message: string; logo_url: string | null }> {
    const form = new FormData();
    form.append('photo', photo, photo.name);
    return this.api.post('company/logo', form);
  }

  removeLogo(): Promise<{ message: string; logo_url: string | null }> {
    return this.api.delete('company/logo');
  }

  subscription(): Promise<Subscription> {
    return this.api.get<Subscription>('company/subscription');
  }
}
