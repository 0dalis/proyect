import { DatePipe, LowerCasePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import {
  AttendanceRecord,
  DashboardData,
  EmployeeRequest,
  MySummary,
  TeamGroup,
} from '../../../core/models';
import { AttendanceService } from '../../../core/services/attendance.service';
import { AuthService } from '../../../core/services/auth.service';
import { DashboardService } from '../../../core/services/dashboard.service';
import { ProfileService } from '../../../core/services/profile.service';
import { RequestService } from '../../../core/services/request.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { PieChartComponent, PieChartSlice } from '../../../shared/components/pie-chart/pie-chart.component';
import { WeatherHeroComponent } from '../../../shared/components/weather-hero/weather-hero.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';
import { StatusChartComponent } from '../../../shared/components/status-chart/status-chart.component';
import { CHANNEL_LABELS, STATUS_LABELS } from '../../../shared/constants/labels';
import { TzDatePipe } from '../../../shared/pipes/tz-date.pipe';
import { TEAM_STATE_META, TEAM_STATE_ORDER } from './team-states';

/** Tipos de solicitud pendientes, en el orden de la tarjeta. */
const REQUEST_TYPES: { key: string; label: string }[] = [
  { key: 'vacation', label: 'Vacaciones' },
  { key: 'leave', label: 'Permisos' },
  { key: 'justification', label: 'Justificaciones' },
  { key: 'late_arrival', label: 'Llegadas tarde' },
  { key: 'early_departure', label: 'Salidas anticipadas' },
];

@Component({
  selector: 'app-dashboard',
  imports: [
    SkeletonComponent,
    RouterLink,
    DatePipe,
    LowerCasePipe,
    WeatherHeroComponent,
    StatusChartComponent,
    PieChartComponent,
    TzDatePipe,
  ],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);
  private readonly dashboardService = inject(DashboardService);
  private readonly attendanceService = inject(AttendanceService);
  private readonly requestService = inject(RequestService);
  private readonly profileService = inject(ProfileService);

  protected readonly today = new Date();
  protected readonly labels = STATUS_LABELS;
  protected readonly channels = CHANNEL_LABELS;
  protected readonly stateMeta = TEAM_STATE_META;
  protected readonly stateOrder = TEAM_STATE_ORDER;
  protected readonly data = signal<DashboardData | null>(null);
  protected readonly mine = signal<MySummary | null>(null);
  protected readonly records = signal<AttendanceRecord[]>([]);
  protected readonly requests = signal<EmployeeRequest[]>([]);

  protected readonly firstName = computed(() => this.auth.user()?.name.split(' ')[0] ?? '');
  protected readonly seesTeam = computed(() => this.auth.role() !== 'employee');

  /** Días del mes: a tiempo, con retardo, faltas y vacaciones o permisos. */
  protected readonly monthSlices = computed<PieChartSlice[]>(() => {
    const month = this.data()?.month;
    if (!month) {
      return [];
    }
    return [
      { key: 'on_time', label: 'A tiempo', value: Math.max(0, month.worked_days - month.lates), color: TEAM_STATE_META.on_time.color, icon: TEAM_STATE_META.on_time.icon },
      { key: 'late', label: 'Con retardo', value: month.lates, color: TEAM_STATE_META.late.color, icon: TEAM_STATE_META.late.icon },
      { key: 'absent', label: 'Faltas sin justificar', value: month.absences, color: TEAM_STATE_META.missing.color, icon: TEAM_STATE_META.missing.icon },
      { key: 'excused', label: 'Vacaciones o permiso', value: month.excused_days, color: TEAM_STATE_META.vacation.color, icon: TEAM_STATE_META.vacation.icon },
    ];
  });

  /** Rebanadas del pastel "Así va el día". */
  protected readonly todaySlices = computed<PieChartSlice[]>(() => {
    const team = this.data()?.team;
    if (!team) {
      return [];
    }
    return TEAM_STATE_ORDER.map((state) => ({
      key: state,
      label: team.labels[state],
      value: team.counts[state],
      color: TEAM_STATE_META[state].color,
      icon: TEAM_STATE_META[state].icon,
    }));
  });

  /** Porcentaje de quienes les tocaba trabajar hoy y ya checaron. */
  protected readonly attendancePercent = computed(() => {
    const team = this.data()?.team;
    return team?.scheduled ? Math.round((team.registered / team.scheduled) * 100) : null;
  });

  /** "1 no ha llegado, 2 con permiso o vacaciones": resumen en palabras. */
  protected readonly todaySummary = computed(() => {
    const team = this.data()?.team;
    if (!team) {
      return '';
    }
    const parts: string[] = [];
    const away = team.counts.vacation + team.counts.leave;
    if (team.counts.missing) {
      parts.push(`${team.counts.missing} sin registro`);
    }
    if (away) {
      parts.push(`${away} con vacaciones o permiso`);
    }
    if (team.counts.upcoming) {
      parts.push(`${team.counts.upcoming} aún no es su hora`);
    }
    return parts.join(' · ');
  });

  protected readonly pendingByType = computed(() => {
    const pending = this.data()?.pending_by_type ?? {};
    return REQUEST_TYPES.map((type) => ({ ...type, total: pending[type.key] ?? 0 })).filter(
      (type) => type.total > 0,
    );
  });

  /** Tendencia de puntualidad contra los 30 días anteriores. */
  protected readonly punctualityTrend = computed(() => {
    const p = this.data()?.punctuality;
    if (p?.current == null || p.previous == null) {
      return null;
    }
    return p.current - p.previous;
  });

  async ngOnInit(): Promise<void> {
    try {
      await this.init();
    } finally {
      this.loading.set(false);
    }
  }

  /** Segmentos de la barra apilada de una oficina o turno. */
  protected segments(group: TeamGroup) {
    return TEAM_STATE_ORDER.filter((state) => group.counts[state] > 0).map((state) => ({
      state,
      value: group.counts[state],
      width: (group.counts[state] / group.total) * 100,
      color: TEAM_STATE_META[state].color,
      label: this.data()?.team.labels[state] ?? state,
    }));
  }

  private async init(): Promise<void> {
    const day = this.today.toLocaleDateString('en-CA');

    try {
      const [dashboard, attendance, requests] = await Promise.all([
        this.dashboardService.get(),
        this.attendanceService.list({ from: day, to: day, per_page: 8 }),
        this.requestService.list({ status: this.seesTeam() ? 'pending' : '', per_page: 8 }),
      ]);
      this.data.set(dashboard);
      this.records.set(attendance.data);
      this.requests.set(
        this.seesTeam() ? requests.data.filter((r) => r.can_review) : requests.data,
      );

      if (this.auth.user()?.employee) {
        this.mine.set(await this.profileService.summary());
      }
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }
}
