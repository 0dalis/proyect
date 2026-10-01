import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Area, Employee, Office, Shift } from '../../../core/models';
import { EmployeeService } from '../../../core/services/employee.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { errorMessage } from '../../../core/utils/error-message';
import { ModalComponent } from '../../../shared/components/modal/modal.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { WEEKDAYS } from '../../../shared/constants/labels';
import { ToastService } from '../../../core/services/toast.service';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

interface ShiftDraft {
  id?: number;
  office_id: number;
  name: string;
  starts_at: string;
  ends_at: string;
  break_minutes: number;
  weekdays: number[];
  /** Días con otro horario, p. ej. viernes de 9:00 a 17:00. */
  special: SpecialDay[];
  tolerance_minutes: number;
  absence_after_minutes: number;
}

interface SpecialDay {
  day: number;
  starts_at: string;
  ends_at: string;
  break_minutes: number;
}

interface AreaDraft {
  id?: number;
  name: string;
  color: string;
  managerIds: number[];
}

@Component({
  selector: 'app-shifts-areas',
  imports: [SkeletonComponent, FormsModule, ModalComponent, PageHeaderComponent],
  templateUrl: './shifts-areas.component.html',
  styleUrl: './shifts-areas.component.scss',
})
export class ShiftsAreasComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly toast = inject(ToastService);
  private readonly organizationService = inject(OrganizationService);
  private readonly employeeService = inject(EmployeeService);

  protected readonly weekdays = WEEKDAYS;
  protected readonly shifts = signal<Shift[]>([]);
  protected readonly areas = signal<Area[]>([]);
  protected readonly offices = signal<Office[]>([]);
  protected readonly employees = signal<Employee[]>([]);
  protected readonly shiftDraft = signal<ShiftDraft | null>(null);
  protected readonly areaDraft = signal<AreaDraft | null>(null);
  protected readonly modalError = signal<string | null>(null);

  async ngOnInit(): Promise<void> {
    const [offices, employees] = await Promise.all([
      this.organizationService.offices(),
      this.employeeService.active(),
    ]);
    this.offices.set(offices);
    this.employees.set(employees);
    await this.load();
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    const [shifts, areas] = await Promise.all([
      this.organizationService.shifts(),
      this.organizationService.areas(),
    ]);
    this.shifts.set(shifts);
    this.areas.set(areas);
  }

  protected openShift(shift?: Shift): void {
    this.modalError.set(null);
    this.shiftDraft.set({
      id: shift?.id,
      office_id: shift?.office_id ?? this.offices()[0]?.id,
      name: shift?.name ?? '',
      starts_at: shift?.starts_at.slice(0, 5) ?? '09:00',
      ends_at: shift?.ends_at.slice(0, 5) ?? '18:00',
      break_minutes: shift?.break_minutes ?? 60,
      weekdays: [...(shift?.weekdays ?? [1, 2, 3, 4, 5])],
      special: Object.entries(shift?.day_schedules ?? {}).map(([day, schedule]) => ({
        day: Number(day),
        starts_at: schedule.starts_at,
        ends_at: schedule.ends_at,
        break_minutes: schedule.break_minutes ?? shift?.break_minutes ?? 60,
      })),
      tolerance_minutes: shift?.tolerance_minutes ?? 15,
      absence_after_minutes: shift?.absence_after_minutes ?? 30,
    });
  }

  protected toggleDay(shift: ShiftDraft, day: number): void {
    shift.weekdays = shift.weekdays.includes(day)
      ? shift.weekdays.filter((d) => d !== day)
      : [...shift.weekdays, day];
    // Un día que ya no trabaja no puede tener horario especial
    shift.special = shift.special.filter((s) => shift.weekdays.includes(s.day));
  }

  /** Días activos que aún no tienen horario especial. */
  protected availableSpecialDays(shift: ShiftDraft, current?: SpecialDay): { value: number; label: string }[] {
    return this.weekdays.filter(
      (d) =>
        shift.weekdays.includes(d.value) &&
        (d.value === current?.day || !shift.special.some((s) => s.day === d.value)),
    );
  }

  protected addSpecialDay(shift: ShiftDraft): void {
    const [first] = this.availableSpecialDays(shift);
    if (!first) {
      return;
    }
    // Propuesta típica: salir una hora antes
    const [h, m] = shift.ends_at.split(':').map(Number);
    const earlier = `${String((h + 23) % 24).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    shift.special = [
      ...shift.special,
      { day: first.value, starts_at: shift.starts_at, ends_at: earlier, break_minutes: shift.break_minutes },
    ];
  }

  protected removeSpecialDay(shift: ShiftDraft, special: SpecialDay): void {
    shift.special = shift.special.filter((s) => s !== special);
  }

  /** "Vie 09:00–17:00" para la tabla de turnos. */
  protected specialLabels(shift: Shift): string[] {
    return Object.entries(shift.day_schedules ?? {}).map(([day, s]) => {
      const short = this.weekdays.find((d) => d.value === Number(day))?.short ?? day;
      return `${short} ${s.starts_at}–${s.ends_at}`;
    });
  }

  protected async saveShift(shift: ShiftDraft): Promise<void> {
    try {
      const { special, ...rest } = shift;
      await this.organizationService.saveShift({
        ...rest,
        day_schedules: Object.fromEntries(
          special.map((s) => [
            String(s.day),
            { starts_at: s.starts_at, ends_at: s.ends_at, break_minutes: Number(s.break_minutes) },
          ]),
        ),
      });
      this.shiftDraft.set(null);
      this.toast.success(`${shift.name} · ${shift.starts_at}–${shift.ends_at}`, {
        title: shift.id ? 'Turno actualizado' : 'Turno creado',
        icon: 'calendar-check',
      });
      await this.load();
    } catch (error) {
      this.modalError.set(errorMessage(error));
    }
  }

  protected openArea(area?: Area): void {
    this.modalError.set(null);
    this.areaDraft.set({
      id: area?.id,
      name: area?.name ?? '',
      color: area?.color ?? '#2563eb',
      managerIds: (area?.managers ?? []).map((m) => m.id),
    });
  }

  protected async saveArea(area: AreaDraft): Promise<void> {
    try {
      const payload = { name: area.name, color: area.color };
      if (area.id) {
        await this.organizationService.saveArea({ id: area.id, ...payload });
        await this.organizationService.syncAreaManagers(area.id, area.managerIds);
      } else {
        await this.organizationService.saveArea(payload);
      }
      this.areaDraft.set(null);
      this.toast.success(area.name, {
        title: area.id ? 'Área actualizada' : 'Área creada',
        icon: 'diagram-3',
      });
      await this.load();
    } catch (error) {
      this.modalError.set(errorMessage(error));
    }
  }
}
