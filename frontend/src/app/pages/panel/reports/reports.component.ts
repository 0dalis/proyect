import { Component, effect, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Area, ReportRow } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { ReportService } from '../../../core/services/report.service';
import { errorMessage } from '../../../core/utils/error-message';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { PeriodPickerComponent } from '../../../shared/components/period-picker/period-picker.component';
import { Period, periodPresets } from '../../../shared/utils/period';
import { ToastService } from '../../../core/services/toast.service';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

type Column = { key: keyof ReportRow['metrics']; label: string; warnAt?: number };

@Component({
  selector: 'app-reports',
  imports: [SkeletonComponent, FormsModule, PeriodPickerComponent, PageHeaderComponent],
  templateUrl: './reports.component.html',
  styleUrl: './reports.component.scss',
})
export class ReportsComponent {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly toast = inject(ToastService);
  private readonly reportService = inject(ReportService);
  private readonly organizationService = inject(OrganizationService);
  private readonly auth = inject(AuthService);

  protected readonly period = signal<Period>(periodPresets()[0].period);
  protected readonly rows = signal<ReportRow[]>([]);
  protected readonly areas = signal<Area[]>([]);
  protected areaId: number | null = null;

  protected readonly columns: Column[] = [
    { key: 'scheduled_days', label: 'Laborables' },
    { key: 'worked_days', label: 'Trabajados' },
    { key: 'on_time', label: 'A tiempo' },
    { key: 'lates', label: 'Retardos' },
    { key: 'unjustified_lates', label: 'Sin justificar', warnAt: 3 },
    { key: 'absences', label: 'Faltas' },
    { key: 'unjustified_absences', label: 'Sin justificar', warnAt: 1 },
    { key: 'early_leaves', label: 'Salidas antic.' },
    { key: 'excused_days', label: 'Vac./permiso' },
    { key: 'minutes_late', label: 'Min. tarde' },
  ];

  constructor() {
    effect(() => {
      this.period();
      this.load();
    });
    if (this.auth.can('organization.manage')) {
      this.organizationService.areas().then((areas) => this.areas.set(areas));
    }
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    this.loading.set(true);
    try {
      this.rows.set(await this.reportService.attendance(this.period(), this.areaId));
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async exportCsv(): Promise<void> {
    try {
      await this.reportService.downloadAttendance(this.period(), this.areaId);
      this.toast.success('Ábrelo con Excel o Google Sheets.', {
        title: 'Reporte descargado',
        icon: 'file-earmark-spreadsheet',
      });
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }
}
