import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { Area, Employee, Office, Shift } from '../../../../core/models';
import { EmployeeService, OrganizeRow } from '../../../../core/services/employee.service';
import { OrganizationService } from '../../../../core/services/organization.service';
import { ToastService } from '../../../../core/services/toast.service';
import { errorMessage } from '../../../../core/utils/error-message';
import { PageHeaderComponent } from '../../../../shared/components/page-header/page-header.component';
import { SkeletonComponent } from '../../../../shared/components/skeleton/skeleton.component';

type EmploymentType = 'permanent' | 'temporary';

interface RowDraft {
  employee: Employee;
  selected: boolean;
  office_id: number | null;
  shift_id: number | null;
  area_id: number | null;
  employment_type: EmploymentType | null;
  contract_ends_on: string;
  app_access: boolean;
  email: string;
}

/**
 * Después de la carga masiva: se seleccionan varios empleados y se les asigna
 * oficina, turno (de esa oficina), área y tipo de una vez; cada quien puede
 * tener o no la app con su correo. Al terminar se regresa a Empleados.
 */
@Component({
  selector: 'app-organize-employees',
  imports: [FormsModule, RouterLink, PageHeaderComponent, SkeletonComponent],
  templateUrl: './organize.component.html',
  styleUrl: './organize.component.scss',
})
export class OrganizeEmployeesComponent implements OnInit {
  private readonly employeeService = inject(EmployeeService);
  private readonly organization = inject(OrganizationService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly rows = signal<RowDraft[]>([]);
  protected readonly offices = signal<Office[]>([]);
  protected readonly shifts = signal<Shift[]>([]);
  protected readonly areas = signal<Area[]>([]);
  protected readonly error = signal<string | null>(null);

  /** Valores para aplicar a los seleccionados. */
  protected bulk: { office_id: number | null; shift_id: number | null; area_id: number | null; employment_type: EmploymentType | null; contract_ends_on: string } = {
    office_id: null,
    shift_id: null,
    area_id: null,
    employment_type: null,
    contract_ends_on: '',
  };

  protected readonly selectedCount = computed(() => this.rows().filter((r) => r.selected).length);
  protected readonly readyCount = computed(() => this.rows().filter((r) => this.isReady(r)).length);
  protected readonly allSelected = computed(() => this.rows().length > 0 && this.rows().every((r) => r.selected));

  async ngOnInit(): Promise<void> {
    try {
      const [pending, offices, shifts, areas] = await Promise.all([
        this.employeeService.pendingSetup(),
        this.organization.offices(),
        this.organization.shifts(),
        this.organization.areas(),
      ]);
      this.offices.set(offices);
      this.shifts.set(shifts);
      this.areas.set(areas);
      this.rows.set(pending.employees.map((employee) => this.toDraft(employee)));
      // Con una sola oficina, se propone de una vez
      if (offices.length === 1) {
        this.bulk.office_id = offices[0].id;
      }
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected shiftsOf(officeId: number | null): Shift[] {
    return this.shifts().filter((s) => s.office_id === Number(officeId));
  }

  protected toggleAll(checked: boolean): void {
    this.rows.update((rows) => rows.map((r) => ({ ...r, selected: checked })));
  }

  protected toggle(row: RowDraft, checked: boolean): void {
    this.rows.update((rows) => rows.map((r) => (r === row ? { ...r, selected: checked } : r)));
  }

  protected onBulkOffice(): void {
    const first = this.shiftsOf(this.bulk.office_id)[0];
    this.bulk.shift_id = first?.id ?? null;
  }

  /** Copia oficina, turno, área y tipo a los seleccionados (solo lo que se eligió). */
  protected applyToSelected(): void {
    const { office_id, shift_id, area_id, employment_type, contract_ends_on } = this.bulk;
    this.rows.update((rows) =>
      rows.map((row) => {
        if (!row.selected) {
          return row;
        }
        const next = { ...row };
        if (office_id) {
          next.office_id = Number(office_id);
          next.shift_id = shift_id ? Number(shift_id) : null;
        }
        if (area_id) next.area_id = Number(area_id);
        if (employment_type) {
          next.employment_type = employment_type;
          if (employment_type === 'temporary' && contract_ends_on) next.contract_ends_on = contract_ends_on;
        }
        return next;
      }),
    );
    this.toast.info(`Aplicado a ${this.selectedCount()} empleados. Revisa y guarda.`, { icon: 'ui-checks' });
  }

  protected onRowOffice(row: RowDraft): void {
    row.shift_id = this.shiftsOf(row.office_id)[0]?.id ?? null;
  }

  protected isReady(row: RowDraft): boolean {
    return (
      !!row.office_id &&
      !!row.shift_id &&
      !!row.area_id &&
      !!row.employment_type &&
      (row.employment_type !== 'temporary' || !!row.contract_ends_on) &&
      (!row.app_access || /^\S+@\S+\.\S+$/.test(row.email.trim()))
    );
  }

  /** Guarda los que ya están completos; los demás siguen pendientes. */
  protected async save(): Promise<void> {
    const ready = this.rows().filter((r) => this.isReady(r));
    if (!ready.length) {
      return;
    }
    this.saving.set(true);
    this.error.set(null);
    try {
      const payload: OrganizeRow[] = ready.map((r) => ({
        id: r.employee.id,
        office_id: Number(r.office_id),
        shift_id: Number(r.shift_id),
        area_id: Number(r.area_id),
        employment_type: r.employment_type!,
        contract_ends_on: r.employment_type === 'temporary' ? r.contract_ends_on : null,
        app_access: r.app_access,
        email: r.app_access ? r.email.trim() : null,
      }));
      const result = await this.employeeService.organize(payload);

      this.toast.success(
        `${result.organized} organizados` + (result.with_app ? ` · ${result.with_app} con acceso a la app` : '') + '.',
        { title: 'Empleados listos', icon: 'people-fill' },
      );

      if (result.pending === 0) {
        await this.router.navigate(['/panel/empleados']);
        return;
      }
      const done = new Set(payload.map((p) => p.id));
      this.rows.update((rows) => rows.filter((r) => !done.has(r.employee.id)));
    } catch (error) {
      this.error.set(errorMessage(error));
    } finally {
      this.saving.set(false);
    }
  }

  private toDraft(employee: Employee): RowDraft {
    return {
      employee,
      selected: false,
      office_id: employee.office_id ?? null,
      shift_id: employee.shift_id ?? null,
      area_id: employee.area_id ?? null,
      employment_type: employee.employment_type ?? null,
      contract_ends_on: employee.contract_ends_on?.slice(0, 10) ?? '',
      app_access: false,
      email: employee.email ?? '',
    };
  }
}
