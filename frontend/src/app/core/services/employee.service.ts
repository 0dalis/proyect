import { inject, Injectable } from '@angular/core';
import {
  ActivityPage,
  CredentialOrientation,
  Employee,
  EmployeeDetail,
  EmployeeStats,
  EmployeeUser,
  Paginated,
  StatsPeriod,
} from '../models';
import { ImportRow } from '../../shared/utils/employee-import';
import { ApiService, QueryParams } from './api.service';

/** Datos para organizar a un empleado importado. */
export interface OrganizeRow {
  id: number;
  office_id: number;
  shift_id: number;
  area_id: number;
  employment_type: 'permanent' | 'temporary';
  contract_ends_on: string | null;
  app_access: boolean;
  email: string | null;
}

export interface RemotePeriodPayload {
  starts_on: string;
  ends_on: string | null;
  weekdays: number[] | null;
  reason?: string;
}

@Injectable({ providedIn: 'root' })
export class EmployeeService {
  private readonly api = inject(ApiService);

  list(params: QueryParams = {}): Promise<Paginated<Employee>> {
    return this.api.get<Paginated<Employee>>('employees', { per_page: 100, ...params });
  }

  /** Todos los activos, para selectores. */
  async active(): Promise<Employee[]> {
    return (await this.list({ status: 'active', per_page: 500 })).data;
  }

  /**
   * `key` es el `public_id` cifrado del empleado, no el id numérico: es lo que
   * viaja en la URL /panel/empleados/... y en las rutas de la API.
   */
  get(key: string): Promise<Employee> {
    return this.api.get<Employee>(`employees/${key}`);
  }

  detail(key: string): Promise<EmployeeDetail> {
    return this.api.get<EmployeeDetail>(`employees/${key}`);
  }

  /** Foto del empleado: se ve en su ficha y en la credencial PDF. */
  uploadPhoto(key: string, photo: File): Promise<{ message: string; photo_url: string | null }> {
    const form = new FormData();
    form.append('photo', photo, photo.name);
    return this.api.post(`employees/${key}/photo`, form);
  }

  removePhoto(key: string): Promise<{ message: string; photo_url: string | null }> {
    return this.api.delete(`employees/${key}/photo`);
  }

  stats(key: string, period: StatsPeriod, date: string): Promise<EmployeeStats> {
    return this.api.get<EmployeeStats>(`employees/${key}/stats`, { period, date });
  }

  activity(key: string, page = 1): Promise<ActivityPage> {
    return this.api.get<ActivityPage>(`employees/${key}/activity`, { page });
  }

  downloadReport(
    employee: Employee,
    period: StatsPeriod,
    date: string,
    calendarCapture?: string | null,
  ): Promise<void> {
    return this.api.download(
      `employees/${employee.public_id}/report.pdf`,
      `reporte-${employee.employee_code}-${date}.pdf`,
      {},
      { period, date, calendar_capture: calendarCapture ?? null },
    );
  }

  deleteRemotePeriod(key: string, periodId: number): Promise<unknown> {
    return this.api.delete(`employees/${key}/remote-periods/${periodId}`);
  }

  /** Carga masiva: nombre, apellidos y sueldo. Quedan "sin organizar". */
  importEmployees(rows: ImportRow[]): Promise<{ created: number; pending: number }> {
    return this.api.post('employees/import', { rows });
  }

  /** Empleados a los que falta oficina, turno, área o tipo. */
  pendingSetup(): Promise<{ count: number; employees: Employee[] }> {
    return this.api.get('employees/setup');
  }

  organize(employees: OrganizeRow[]): Promise<{ organized: number; with_app: number; pending: number }> {
    return this.api.post('employees/organize', { employees });
  }

  create(payload: Record<string, unknown>): Promise<Employee> {
    return this.api.post<Employee>('employees', payload);
  }

  update(key: string, payload: Record<string, unknown>): Promise<Employee> {
    return this.api.put<Employee>(`employees/${key}`, payload);
  }

  /**
   * Interruptor "Acceso a la app". Al activarlo, Laravel crea el usuario y le
   * envía al empleado el código de empresa y una contraseña temporal.
   */
  setAppAccess(key: string, enabled: boolean, email?: string): Promise<{ user: EmployeeUser | null }> {
    return this.api.put(`employees/${key}/app-access`, { enabled, email });
  }

  /** Nueva contraseña temporal por correo. */
  resendAppAccess(key: string): Promise<{ message: string }> {
    return this.api.post(`employees/${key}/app-access/resend`);
  }

  addRemotePeriod(key: string, payload: RemotePeriodPayload): Promise<unknown> {
    return this.api.post(`employees/${key}/remote-periods`, payload);
  }

  reissueBadge(key: string): Promise<{ message: string }> {
    return this.api.post(`employees/${key}/badge`);
  }

  /** PDF del servidor (respaldo cuando el navegador no puede capturar la tarjeta). */
  downloadBadge(
    employee: Pick<Employee, 'public_id' | 'employee_code'>,
    orientation: CredentialOrientation = 'horizontal',
  ): Promise<void> {
    return this.api.download(
      `employees/${employee.public_id}/badge.pdf`,
      `credencial-${employee.employee_code}.pdf`,
      { orientation },
    );
  }

  /** Frente y reverso capturados en pantalla, listos para imprimir. */
  renderBadge(
    employee: Pick<Employee, 'public_id' | 'employee_code'>,
    captures: { front: string; back: string },
    orientation: CredentialOrientation,
  ): Promise<Blob> {
    return this.api.blob(`employees/${employee.public_id}/badge.pdf`, { ...captures, orientation });
  }

  downloadBadges(ids: number[], orientation: CredentialOrientation = 'horizontal'): Promise<void> {
    return this.api.download('badges.pdf', 'credenciales.pdf', {}, { employee_ids: ids, orientation });
  }
}
