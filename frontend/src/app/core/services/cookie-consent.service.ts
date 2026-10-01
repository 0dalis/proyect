import { Injectable, signal } from '@angular/core';
import { LEGAL } from '../../shared/constants/legal';

export type CookieChoice = 'essential' | 'all';

const STORAGE_KEY = 'asist.cookie-consent';

interface StoredConsent {
  choice: CookieChoice;
  version: string;
  at: string;
}

/**
 * Recuerda la respuesta al aviso de cookies. Si cambia la versión de la
 * política, se vuelve a preguntar.
 */
@Injectable({ providedIn: 'root' })
export class CookieConsentService {
  readonly choice = signal<CookieChoice | null>(this.read());

  accept(choice: CookieChoice): void {
    const consent: StoredConsent = { choice, version: LEGAL.version, at: new Date().toISOString() };
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
    } catch {
      // Sin almacenamiento: se volverá a preguntar la próxima vez
    }
    this.choice.set(choice);
  }

  reset(): void {
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch {
      // nada que limpiar
    }
    this.choice.set(null);
  }

  private read(): CookieChoice | null {
    try {
      const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null') as StoredConsent | null;
      return saved?.version === LEGAL.version ? saved.choice : null;
    } catch {
      return null;
    }
  }
}
