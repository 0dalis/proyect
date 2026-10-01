import { inject, Injectable } from '@angular/core';
import { ApiService } from './api.service';

export interface PublicConfig {
  recaptcha_site_key: string | null;
  support_email: string;
  legal_version: string;
}

/**
 * Claves públicas que viven en el .env de Laravel (reCAPTCHA, soporte).
 * Se piden una sola vez.
 */
@Injectable({ providedIn: 'root' })
export class PublicConfigService {
  private readonly api = inject(ApiService);
  private request: Promise<PublicConfig> | null = null;

  load(): Promise<PublicConfig> {
    this.request ??= this.api.get<PublicConfig>('config').catch((error) => {
      this.request = null;
      throw error;
    });
    return this.request;
  }
}
