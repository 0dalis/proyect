import { DatePipe, DecimalPipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AttendanceRecord, Employee } from '../../../core/models';
import { AttendanceService } from '../../../core/services/attendance.service';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { errorMessage } from '../../../core/utils/error-message';
import { GeofenceMapComponent } from '../../../shared/components/geofence-map/geofence-map.component';
import { ModalComponent } from '../../../shared/components/modal/modal.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { CHANNEL_LABELS, STATUS_LABELS } from '../../../shared/constants/labels';
import { ToastService } from '../../../core/services/toast.service';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';
import { TzDatePipe } from '../../../shared/pipes/tz-date.pipe';

@Component({
  selector: 'app-attendance',
  imports: [
    SkeletonComponent,
    FormsModule,
    DatePipe,
    DecimalPipe,
    GeofenceMapComponent,
    ModalComponent,
    PageHeaderComponent, TzDatePipe,
  ],
  templateUrl: './attendance.component.html',
  styleUrl: './attendance.component.scss',
})
export class AttendanceComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);
  private readonly attendanceService = inject(AttendanceService);
  private readonly employeeService = inject(EmployeeService);

  protected readonly labels = STATUS_LABELS;
  protected readonly channels = CHANNEL_LABELS;
  protected readonly canManage = this.auth.can('attendance.manage');
  protected readonly myEmployeeId = this.auth.user()?.employee?.id ?? null;
  protected readonly records = signal<AttendanceRecord[]>([]);
  protected readonly employees = signal<Employee[]>([]);
  protected readonly selected = signal<AttendanceRecord | null>(null);
  protected readonly manualOpen = signal(false);
  protected readonly manualError = signal<string | null>(null);

  protected from = new Date(Date.now() - 6 * 86400000).toLocaleDateString('en-CA');
  protected to = new Date().toLocaleDateString('en-CA');
  protected status = '';
  protected onlyMine = false;
  protected manual = { employee_id: 0, recorded_at: '' };

  ngOnInit(): Promise<void> {
    return this.load();
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    try {
      const page = await this.attendanceService.list({
        from: this.from,
        to: this.to,
        status: this.status,
        employee_id: this.onlyMine ? this.myEmployeeId : null,
      });
      this.records.set(page.data);
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async openManual(): Promise<void> {
    this.manualError.set(null);
    if (!this.employees().length) {
      this.employees.set(await this.employeeService.active());
    }
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    this.manual = {
      employee_id: this.employees()[0]?.id ?? 0,
      // Hora local (no UTC): Laravel la interpreta en la zona de la oficina del empleado
      recorded_at: new Date(now.getTime() - now.getTimezoneOffset() * 60_000).toISOString().slice(0, 16),
    };
    this.manualOpen.set(true);
  }

  protected async saveManual(): Promise<void> {
    try {
      const result = await this.attendanceService.registerManual(
        this.manual.employee_id,
        this.manual.recorded_at.replace('T', ' '),
      );
      this.manualOpen.set(false);
      this.toast.success(`${result.type_label} · ${result.status_label}.`, {
        title: 'Registro manual guardado',
        icon: 'clock-history',
      });
      await this.load();
    } catch (error) {
      this.manualError.set(errorMessage(error));
    }
  }

  protected async toggleJustified(record: AttendanceRecord): Promise<void> {
    try {
      await this.attendanceService.setJustified(record.id, !record.is_justified);
      this.toast.success(
        record.is_justified
          ? 'La checada vuelve a contar para bonos.'
          : 'Esta checada ya no cuenta contra bonos.',
        { title: record.is_justified ? 'Justificación quitada' : 'Checada justificada' },
      );
      this.records.update((items) =>
        items.map((r) => (r.id === record.id ? { ...r, is_justified: !r.is_justified } : r)),
      );
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  /** 485 → "8 h 05 min" */
  protected hours(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m ? `${h} h ${String(m).padStart(2, '0')} min` : `${h} h`;
  }
}
