import { HttpHeaders } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { KioskInfo, PunchResult } from '../models';
import { ApiService } from './api.service';

const TOKEN_KEY = 'asist.kiosk-token';

/** "Consultar mi asistencia" en el kiosko (últimos 14 días). */
export interface KioskAttendance {
  employee: { name: string; employee_code: string };
  from: string;
  late_count: number;
  days: {
    date: string;
    check_in: string | null;
    check_out: string | null;
    status: string | null;
    status_label: string | null;
    minutes_late: number;
    is_justified: boolean;
  }[];
}

export interface KioskNews {
  id: number;
  title: string;
  body: string;
  created_at: string;
}

/**
 * API de la pantalla del kiosko. Se autentica con su propio token, no con
 * la sesión de un usuario.
 */
@Injectable({ providedIn: 'root' })
export class KioskTerminalService {
  private readonly api = inject(ApiService);

  storedToken(): string | null {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  }

  saveToken(token: string): void {
    try {
      localStorage.setItem(TOKEN_KEY, token);
    } catch {
      // Sin almacenamiento: habrá que activar de nuevo al recargar
    }
  }

  forgetToken(): void {
    try {
      localStorage.removeItem(TOKEN_KEY);
    } catch {
      // nada que limpiar
    }
  }

  /** El kiosko también manda el token CSRF en sus checadas (mismo origen que el panel). */
  prepare(): Promise<void> {
    return this.api.csrfCookie();
  }

  me(token: string): Promise<KioskInfo> {
    return this.api.get<KioskInfo>('kiosk/me', {}, this.headers(token));
  }

  news(token: string): Promise<KioskNews[]> {
    return this.api.get<KioskNews[]>('kiosk/news', {}, this.headers(token));
  }

  punch(token: string, payload: Record<string, string | null>): Promise<PunchResult> {
    return this.api.post<PunchResult>('kiosk/punch', payload, this.headers(token));
  }

  myAttendance(token: string, payload: Record<string, string>): Promise<KioskAttendance> {
    return this.api.post<KioskAttendance>('kiosk/my-attendance', payload, this.headers(token));
  }

  private headers(token: string): HttpHeaders {
    return new HttpHeaders({ 'X-Kiosk-Token': token });
  }
}
