import { DatePipe } from '@angular/common';
import { Component, computed, ElementRef, inject, input, OnInit, signal, ViewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import {
  ActivityLogEntry,
  Area,
  EmployeeDay,
  EmployeeDetail,
  EmployeeStats,
  Office,
  Shift,
  StatsPeriod,
} from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { DialogService } from '../../../core/services/dialog.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { ActivityTimelineComponent } from '../../../shared/components/activity-timeline/activity-timeline.component';
import { CredentialViewerComponent } from '../../../shared/components/credential-viewer/credential-viewer.component';
import { ImageUploadComponent } from '../../../shared/components/image-upload/image-upload.component';
import {
  InlineFieldComponent,
  InlineOption,
} from '../../../shared/components/inline-field/inline-field.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';
import {
  StatusChartComponent,
  StatusPoint,
} from '../../../shared/components/status-chart/status-chart.component';
import { REQUEST_TYPE_LABELS, STATUS_LABELS, WEEKDAYS } from '../../../shared/constants/labels';
import { VacationBalanceComponent } from '../../../shared/components/vacation-balance/vacation-balance.component';

const DAY_LABELS: Record<string, string> = {
  on_time: 'A tiempo',
  late: 'Retardo',
  absent: 'Falta',
  excused: 'Vacaciones / permiso',
  rest: 'Descanso',
  holiday: 'Festivo',
  pending: 'Pendiente',
  not_employed: '—',
};

/**
 * Detalle de un empleado: datos editables en su lugar, asistencia por semana,
 * mes o año, reporte PDF, credencial, acceso a la app, home office e historial.
 */
@Component({
  selector: 'app-employee-detail',
  imports: [
    DatePipe,
    FormsModule,
    RouterLink,
    InlineFieldComponent,
    StatusChartComponent,
    ActivityTimelineComponent,
    SkeletonComponent,
    VacationBalanceComponent,
    ImageUploadComponent,
    CredentialViewerComponent,
  ],
  templateUrl: './employee-detail.component.html',
  styleUrl: './employee-detail.component.scss',
})
export class EmployeeDetailComponent implements OnInit {
  protected readonly auth = inject(AuthService);
  private readonly employeeService = inject(EmployeeService);
  private readonly organizationService = inject(OrganizationService);
  private readonly toast = inject(ToastService);
  private readonly dialog = inject(DialogService);

  /** Viene de la ruta /panel/empleados/:id */
  readonly id = input.required<string>();

  /** El calendario visible, que se captura para el PDF del reporte. */
  @ViewChild('calendarCard') private calendarCard?: ElementRef<HTMLElement>;

  protected readonly labels = STATUS_LABELS;
  protected readonly dayLabels = DAY_LABELS;
  protected readonly weekdays = WEEKDAYS;
  /** Los empleados fuera del límite del plan se consultan, pero no se modifican. */
  protected get canEdit(): boolean {
    return this.auth.can('employees.manage') && !this.employee()?.locked_by_plan;
  }
  protected readonly canPayroll =
    this.auth.can('payroll.manage') && !!this.auth.user()?.company.payroll_enabled;
  protected readonly canKiosks = this.auth.can('kiosks.manage');
  protected readonly canUsers = this.auth.can('users.manage');

  protected readonly employee = signal<EmployeeDetail | null>(null);
  protected readonly offices = signal<Office[]>([]);
  protected readonly shifts = signal<Shift[]>([]);
  protected readonly areas = signal<Area[]>([]);
  protected readonly loading = signal(true);
  /** Subida de la foto del empleado en curso. */
  protected readonly uploadingPhoto = signal(false);
  /** Preview de la credencial: frente, reverso y orientación. */
  protected readonly credentialOpen = signal(false);

  protected readonly period = signal<StatsPeriod>('month');
  protected readonly anchor = signal(new Date());
  protected readonly stats = signal<EmployeeStats | null>(null);
  protected readonly statsLoading = signal(true);

  // ---------- Calendario de asistencia ----------
  protected readonly calendarWeekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
  protected readonly calendarAnchor = signal(startOfMonth(new Date()));
  protected readonly calendarDays = signal<EmployeeDay[]>([]);
  protected readonly calendarLoading = signal(true);

  /** Mes de alta del empleado: el calendario no retrocede más allá. */
  protected readonly calendarMin = computed(() => {
    const employee = this.employee();
    const raw = employee?.created_at ?? employee?.hired_on ?? null;
    const date = raw ? new Date(raw) : new Date();
    return startOfMonth(date);
  });

  protected readonly calendarLabel = computed(() =>
    this.calendarAnchor().toLocaleDateString('es-MX', { month: 'long', year: 'numeric' }),
  );

  protected readonly canGoPrev = computed(() => this.calendarAnchor() > this.calendarMin());
  protected readonly canGoNext = computed(() => this.calendarAnchor() < startOfMonth(new Date()));

  /** Celdas de la cuadrícula: huecos al inicio y final para cuadrar semanas (lunes primero). */
  protected readonly calendarCells = computed<(EmployeeDay | null)[]>(() => {
    const days = this.calendarDays();
    const anchor = this.calendarAnchor();
    const lead = (new Date(anchor.getFullYear(), anchor.getMonth(), 1).getDay() + 6) % 7;
    const cells: (EmployeeDay | null)[] = Array(lead).fill(null);
    cells.push(...days);
    while (cells.length % 7 !== 0) {
      cells.push(null);
    }
    return cells;
  });

  protected readonly calendarLegend: { tone: string; label: string }[] = [
    { tone: 'on-time', label: 'A tiempo' },
    { tone: 'late', label: 'Retardo' },
    { tone: 'absent', label: 'Falta' },
    { tone: 'excused', label: 'Permiso' },
    { tone: 'justified', label: 'Justificada' },
    { tone: 'holiday', label: 'Festivo' },
    { tone: 'rest', label: 'No laborable' },
    { tone: 'pending', label: 'Por venir' },
  ];

  /** Hoy en la zona del navegador, para el anillo del día actual. */
  protected readonly calendarToday = new Date().toLocaleDateString('en-CA');

  protected readonly activity = signal<ActivityLogEntry[]>([]);
  protected readonly activityActions = signal<Record<string, string>>({});
  protected readonly activityPage = signal(1);
  protected readonly activityLastPage = signal(1);

  /** Correo con el que se activará la app (se precarga con el del empleado). */
  protected appEmail = '';
  protected readonly appFormOpen = signal(false);
  protected readonly appSaving = signal(false);
  protected remote = { starts_on: '', ends_on: '', days: new Set<number>() };

  // ---------- Opciones de los campos editables ----------
  protected readonly areaOptions = computed<InlineOption[]>(() =>
    this.areas().map((a) => ({ value: a.id, label: a.name })),
  );
  protected readonly officeOptions = computed<InlineOption[]>(() =>
    this.offices().map((o) => ({ value: o.id, label: o.name })),
  );
  protected readonly shiftOptions = computed<InlineOption[]>(() =>
    this.shifts()
      .filter((s) => s.office_id === this.employee()?.office_id)
      .map((s) => ({
        value: s.id,
        label: `${s.name} (${s.starts_at.slice(0, 5)}–${s.ends_at.slice(0, 5)})`,
      })),
  );
  protected readonly statusOptions: InlineOption[] = [
    { value: 'active', label: 'Activo' },
    { value: 'inactive', label: 'Inactivo' },
    { value: 'terminated', label: 'Baja (bloquea su acceso)' },
  ];
  protected readonly workModeOptions: InlineOption[] = [
    { value: 'onsite', label: 'Presencial (con geocerca)' },
    { value: 'remote', label: 'Home office permanente' },
  ];
  protected readonly employmentOptions: InlineOption[] = [
    { value: 'permanent', label: 'Planta' },
    { value: 'temporary', label: 'Temporal' },
  ];
  protected readonly salaryPeriodOptions: InlineOption[] = [
    { value: 'daily', label: 'Diario' },
    { value: 'weekly', label: 'Semanal' },
    { value: 'biweekly', label: 'Quincenal' },
    { value: 'monthly', label: 'Mensual' },
  ];

  protected readonly initials = computed(() => {
    const e = this.employee();
    return e ? `${e.first_name.charAt(0)}${e.last_name.charAt(0)}`.toUpperCase() : '';
  });

  // ---------- Estadísticas ----------
  protected readonly chartPoints = computed<StatusPoint[]>(() =>
    (this.stats()?.buckets ?? []).map((b) => ({
      label: b.label,
      on_time: b.on_time,
      late: b.late,
      absent: b.absent,
      excused: b.excused,
    })),
  );

  protected readonly rangeLabel = computed(() => {
    const stats = this.stats();
    if (!stats) {
      return '';
    }
    const from = new Date(`${stats.period.from}T12:00:00`);
    const to = new Date(`${stats.period.to}T12:00:00`);
    const format = (date: Date, options: Intl.DateTimeFormatOptions) =>
      date.toLocaleDateString('es-MX', options);
    return {
      week: `${format(from, { day: 'numeric', month: 'short' })} – ${format(to, { day: 'numeric', month: 'short', year: 'numeric' })}`,
      month: format(from, { month: 'long', year: 'numeric' }),
      year: String(from.getFullYear()),
    }[stats.period.type];
  });

  protected readonly visibleDays = computed(() =>
    (this.stats()?.days ?? []).filter((d) => d.status !== 'not_employed').reverse(),
  );

  async ngOnInit(): Promise<void> {
    const lookups = this.canEdit && this.auth.can('organization.manage');
    try {
      const [employee] = await Promise.all([
        this.employeeService.detail(this.id()),
        lookups ? this.loadLookups() : Promise.resolve(),
      ]);
      this.employee.set(employee);
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se pudo abrir el empleado' });
    } finally {
      this.loading.set(false);
    }
    await Promise.all([this.loadStats(), this.loadActivity(), this.loadCalendar()]);
  }

  // ---------- Edición en línea ----------
  /** Devuelve la función que guarda un campo; el componente de campo la llama. */
  protected saveField(field: string, label: string): (value: string) => Promise<void> {
    let save = this.saveFns.get(field);
    if (!save) {
      save = this.createSave(field, label);
      this.saveFns.set(field, save);
    }
    return save;
  }

  private readonly saveFns = new Map<string, (value: string) => Promise<void>>();

  private createSave(field: string, label: string): (value: string) => Promise<void> {
    return async (value: string) => {
      const employee = this.employee()!;
      const payload: Record<string, unknown> = { [field]: this.parse(field, value) };

      if (field === 'office_id') {
        // Al cambiar de oficina se asigna el primer turno de esa oficina
        const shift = this.shifts().find((s) => s.office_id === Number(value));
        if (!shift) {
          this.toast.error('Esa oficina no tiene turnos. Crea uno en Turnos y áreas.');
          throw new Error('sin turno');
        }
        payload['shift_id'] = shift.id;
      }

      if (field === 'status' && value === 'terminated' && employee.status !== 'terminated') {
        const confirmed = await this.dialog.confirm({
          title: `¿Dar de baja a ${employee.first_name} ${employee.last_name}?`,
          text: 'Se bloqueará su acceso a la app y al panel. Su historial se conserva.',
          confirmText: 'Dar de baja',
          variant: 'danger',
        });
        if (!confirmed) {
          throw new Error('cancelado');
        }
      }

      try {
        await this.employeeService.update(employee.public_id, payload);
        this.toast.success(
          field === 'pin' ? 'El PIN anterior ya no funciona.' : `${label} actualizado.`,
          {
            title: 'Cambio guardado',
            duration: 2800,
          },
        );
        await Promise.all([this.refresh(), this.loadActivity()]);
      } catch (error) {
        this.toast.error(errorMessage(error), { title: `No se guardó ${label.toLowerCase()}` });
        throw error;
      }
    };
  }

  private parse(field: string, value: string): unknown {
    if (['office_id', 'shift_id', 'area_id'].includes(field)) {
      return Number(value);
    }
    if (field === 'salary') {
      return value === '' ? null : Number(value);
    }
    return value === '' ? null : value;
  }

  protected nameOf(list: { id: number; name: string }[], id: number | undefined): string | null {
    return list.find((item) => item.id === id)?.name ?? null;
  }

  // ---------- Estadísticas ----------
  protected setPeriod(period: StatsPeriod): void {
    this.period.set(period);
    this.anchor.set(new Date());
    this.loadStats();
  }

  protected move(step: number): void {
    const next = new Date(this.anchor());
    if (this.period() === 'week') {
      next.setDate(next.getDate() + step * 7);
    } else if (this.period() === 'month') {
      next.setMonth(next.getMonth() + step, 1);
    } else {
      next.setFullYear(next.getFullYear() + step);
    }
    this.anchor.set(next);
    this.loadStats();
  }

  private async loadStats(): Promise<void> {
    this.statsLoading.set(true);
    try {
      this.stats.set(
        await this.employeeService.stats(this.id(), this.period(), this.anchorIso()),
      );
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.statsLoading.set(false);
    }
  }

  // ---------- Calendario ----------
  protected moveCalendar(step: number): void {
    const next = new Date(this.calendarAnchor());
    next.setMonth(next.getMonth() + step, 1);
    if (next < this.calendarMin() || next > startOfMonth(new Date())) {
      return;
    }
    this.calendarAnchor.set(next);
    this.loadCalendar();
  }

  private async loadCalendar(): Promise<void> {
    this.calendarLoading.set(true);
    try {
      const iso = this.calendarAnchor().toLocaleDateString('en-CA');
      const stats = await this.employeeService.stats(this.id(), 'month', iso);
      this.calendarDays.set(stats.days);
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.calendarLoading.set(false);
    }
  }

  protected dayNumber(day: EmployeeDay): number {
    return Number(day.date.slice(8, 10));
  }

  protected isToday(day: EmployeeDay): boolean {
    return day.date === this.calendarToday;
  }

  protected dayTone(day: EmployeeDay): string {
    switch (day.status) {
      case 'on_time':
        return 'on-time';
      case 'late':
        return 'late';
      case 'absent':
        return day.justified ? 'justified' : 'absent';
      case 'excused':
        return 'excused';
      case 'holiday':
        return 'holiday';
      case 'rest':
        return 'rest';
      case 'pending':
        return 'pending';
      default:
        return 'off';
    }
  }

  protected dayIcon(day: EmployeeDay): string | null {
    if (day.status === 'absent') {
      return day.justified ? 'bi-bell' : 'bi-x-lg';
    }
    if (day.status === 'excused') {
      const icons: Record<string, string> = {
        vacation: 'bi-airplane',
        justification: 'bi-info-circle',
        leave: 'bi-exclamation-circle',
      };
      return icons[day.excused_type ?? ''] ?? 'bi-exclamation-circle';
    }
    const icons: Record<string, string> = {
      holiday: 'bi-star',
      late: 'bi-clock',
      on_time: 'bi-check2',
    };
    return icons[day.status] ?? null;
  }

  protected dayTitle(day: EmployeeDay): string {
    const parts: string[] = [];

    if (day.status === 'excused' && day.excused_type) {
      parts.push(REQUEST_TYPE_LABELS[day.excused_type] ?? 'Permiso');
    } else if (day.status === 'absent' && day.justified) {
      parts.push('Falta justificada');
    } else if (day.status === 'holiday' && day.holiday) {
      parts.push(`Festivo: ${day.holiday}`);
    } else {
      parts.push(this.dayLabels[day.status] ?? day.status);
    }

    if (day.check_in) {
      parts.push(`Entrada ${day.check_in}`);
    }
    if (day.check_out) {
      parts.push(`Salida ${day.check_out}`);
    }
    if (day.minutes_late) {
      parts.push(`${day.minutes_late} min tarde`);
    }

    return parts.join(' · ');
  }

  protected async downloadReport(): Promise<void> {
    const employee = this.employee();
    if (!employee) {
      return;
    }
    try {
      const calendarCapture = await this.captureCalendar();
      await this.employeeService.downloadReport(
        employee,
        this.period(),
        this.anchorIso(),
        calendarCapture,
      );
      this.toast.success(`Periodo: ${this.rangeLabel()}.`, {
        title: 'Reporte descargado',
        icon: 'file-earmark-pdf',
      });
      await this.loadActivity();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  /**
   * Foto del calendario tal como se ve en pantalla, para imprimirla al final
   * del PDF. Si no se puede (todavía cargando, sin datos, navegador sin
   * canvas) el reporte se descarga igual, sin la imagen.
   */
  private async captureCalendar(): Promise<string | null> {
    const node = this.calendarCard?.nativeElement;
    if (!node || this.calendarLoading() || this.calendarCells().length === 0) {
      return null;
    }

    try {
      // Se carga solo al descargar el reporte, no en el bundle inicial.
      const { default: html2canvas } = await import('html2canvas');

      // Sin botones de mes ni animaciones a medio camino durante la foto.
      node.classList.add('is-capturing');
      void node.offsetWidth;

      try {
        const surface =
          getComputedStyle(document.documentElement).getPropertyValue('--color-surface').trim() ||
          '#ffffff';
        const canvas = await html2canvas(node, { backgroundColor: surface, scale: 2 });
        return canvas.toDataURL('image/png');
      } finally {
        node.classList.remove('is-capturing');
      }
    } catch {
      return null;
    }
  }

  // ---------- Credencial ----------
  protected async downloadBadge(): Promise<void> {
    try {
      await this.employeeService.downloadBadge(this.employee()!);
      this.toast.success('Lista para imprimir.', {
        title: 'Credencial descargada',
        icon: 'person-badge',
      });
      await this.loadActivity();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async reissueBadge(): Promise<void> {
    const employee = this.employee()!;
    const confirmed = await this.dialog.confirm({
      title: '¿Invalidar la credencial actual?',
      text: `El QR impreso de ${employee.first_name} dejará de funcionar en los kioskos.`,
      confirmText: 'Invalidar y generar nueva',
      variant: 'warning',
    });
    if (!confirmed) {
      return;
    }
    try {
      await this.employeeService.reissueBadge(employee.public_id);
      this.toast.success('La credencial anterior ya no funciona.', {
        title: 'Credencial nueva',
        icon: 'qr-code',
      });
      await this.refresh();
      await this.downloadBadge();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  // ---------- Foto del empleado ----------
  protected async uploadPhoto(file: File): Promise<void> {
    const employee = this.employee()!;
    this.uploadingPhoto.set(true);
    try {
      const { photo_url } = await this.employeeService.uploadPhoto(employee.public_id, file);
      this.employee.update((current) => (current ? { ...current, photo_url } : current));
      this.toast.success('Ya aparece en la ficha y en la credencial PDF.', {
        title: 'Foto actualizada',
        icon: 'person-badge',
      });
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se pudo subir la foto' });
    } finally {
      this.uploadingPhoto.set(false);
    }
  }

  protected async removePhoto(): Promise<void> {
    const employee = this.employee()!;
    this.uploadingPhoto.set(true);
    try {
      const { photo_url } = await this.employeeService.removePhoto(employee.public_id);
      this.employee.update((current) => (current ? { ...current, photo_url } : current));
      this.toast.info('La credencial volverá a mostrar sus iniciales.', {
        title: 'Foto eliminada',
        icon: 'person-x',
      });
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se pudo quitar la foto' });
    } finally {
      this.uploadingPhoto.set(false);
    }
  }

  // ---------- Acceso a la app ----------
  /** Interruptor: encenderlo pide el correo; apagarlo pide confirmación. */
  protected async toggleApp(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const employee = this.employee()!;

    if (input.checked) {
      input.checked = false;
      this.appEmail = employee.user?.email ?? employee.email ?? '';
      this.appFormOpen.set(true);
      return;
    }

    input.checked = true;
    const confirmed = await this.dialog.confirm({
      title: `¿Quitar la app a ${employee.first_name}?`,
      text: 'Se cerrará su sesión en el celular. Podrá seguir checando en el kiosko con su PIN o credencial.',
      confirmText: 'Quitar app',
      variant: 'danger',
    });
    if (confirmed) {
      await this.saveApp(false);
    }
  }

  protected async saveApp(enabled: boolean): Promise<void> {
    const employee = this.employee()!;
    this.appSaving.set(true);
    try {
      await this.employeeService.setAppAccess(employee.public_id, enabled, enabled ? this.appEmail.trim() : undefined);
      if (enabled) {
        this.toast.success(
          `Le enviamos a ${this.appEmail.trim()} su código de empresa y una contraseña temporal.`,
          { title: 'App activada', icon: 'phone' },
        );
      } else {
        this.toast.info(`${employee.first_name} ya no puede usar la app.`, { title: 'App desactivada' });
      }
      this.appFormOpen.set(false);
      await Promise.all([this.refresh(), this.loadActivity()]);
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se guardó' });
    } finally {
      this.appSaving.set(false);
    }
  }

  protected async resendApp(): Promise<void> {
    const employee = this.employee()!;
    try {
      const { message } = await this.employeeService.resendAppAccess(employee.public_id);
      this.toast.success(message, { title: 'Acceso reenviado', icon: 'envelope-check' });
      await this.loadActivity();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  // ---------- Home office ----------
  protected toggleRemoteDay(day: number): void {
    this.remote.days.has(day) ? this.remote.days.delete(day) : this.remote.days.add(day);
  }

  protected async addRemote(): Promise<void> {
    try {
      await this.employeeService.addRemotePeriod(this.employee()!.public_id, {
        starts_on: this.remote.starts_on,
        ends_on: this.remote.ends_on || null,
        weekdays: this.remote.days.size ? [...this.remote.days].sort() : null,
      });
      this.toast.success('Esos días no se validará la geocerca.', {
        title: 'Home office agregado',
        icon: 'house-door-fill',
      });
      this.remote = { starts_on: '', ends_on: '', days: new Set() };
      await Promise.all([this.refresh(), this.loadActivity()]);
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async removeRemote(periodId: number): Promise<void> {
    const confirmed = await this.dialog.confirm({
      title: '¿Quitar este periodo de home office?',
      text: 'Esos días volverá a validarse la geocerca.',
      confirmText: 'Quitar',
      variant: 'danger',
    });
    if (!confirmed) {
      return;
    }
    try {
      await this.employeeService.deleteRemotePeriod(this.employee()!.public_id, periodId);
      this.toast.success('Periodo eliminado.', { icon: 'trash3' });
      await Promise.all([this.refresh(), this.loadActivity()]);
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected weekdayNames(days: number[] | null): string {
    return days?.length
      ? WEEKDAYS.filter((d) => days.includes(d.value))
          .map((d) => d.label.slice(0, 3))
          .join(', ')
      : 'Todos los días';
  }

  // ---------- Historial ----------
  protected async loadActivity(page = 1): Promise<void> {
    try {
      const result = await this.employeeService.activity(this.id(), page);
      this.activity.set(page === 1 ? result.data : [...this.activity(), ...result.data]);
      this.activityActions.set(result.actions);
      this.activityPage.set(result.current_page);
      this.activityLastPage.set(result.last_page);
    } catch {
      // El historial no es crítico para usar la pantalla
    }
  }

  // ---------- Utilidades ----------
  private async refresh(): Promise<void> {
    this.employee.set(await this.employeeService.detail(this.id()));
  }

  private async loadLookups(): Promise<void> {
    const [offices, shifts, areas] = await Promise.all([
      this.organizationService.offices(),
      this.organizationService.shifts(),
      this.organizationService.areas(),
    ]);
    this.offices.set(offices);
    this.shifts.set(shifts);
    this.areas.set(areas);
  }

  private anchorIso(): string {
    return this.anchor().toLocaleDateString('en-CA');
  }

  /** 485 → "8:05 h" */
  protected hours(minutes: number): string {
    return `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')} h`;
  }
}

/** Primer día del mes a las 00:00, para comparar y navegar el calendario. */
function startOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}
