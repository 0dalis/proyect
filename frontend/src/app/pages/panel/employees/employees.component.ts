import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import {
  Area,
  CredentialOrientation,
  Employee,
  EmployeeDetail,
  Office,
  Shift,
} from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import {
  CredentialViewerComponent,
  readCredentialOrientation,
  saveCredentialOrientation,
} from '../../../shared/components/credential-viewer/credential-viewer.component';
import { ModalComponent } from '../../../shared/components/modal/modal.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';
import { STATUS_LABELS } from '../../../shared/constants/labels';
import { ImportModalComponent } from './import-modal/import-modal.component';

/**
 * Lista de empleados. Para ver o editar a alguien se abre su detalle
 * (edición en línea); aquí se dan de alta, uno por uno o con la carga masiva.
 */
@Component({
  selector: 'app-employees',
  imports: [
    ReactiveFormsModule,
    RouterLink,
    PageHeaderComponent,
    ModalComponent,
    SkeletonComponent,
    ImportModalComponent,
    CredentialViewerComponent,
  ],
  templateUrl: './employees.component.html',
  styleUrl: './employees.component.scss',
})
export class EmployeesComponent implements OnInit {
  protected readonly auth = inject(AuthService);
  private readonly employeeService = inject(EmployeeService);
  private readonly organizationService = inject(OrganizationService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);

  protected readonly labels = STATUS_LABELS;
  protected readonly canManage = this.auth.can('employees.manage');
  protected readonly canManagePayroll =
    this.auth.can('payroll.manage') && !!this.auth.user()?.company.payroll_enabled;

  protected readonly employees = signal<Employee[]>([]);
  protected readonly total = signal(0);
  protected readonly loading = signal(true);
  protected readonly offices = signal<Office[]>([]);
  protected readonly shifts = signal<Shift[]>([]);
  protected readonly areas = signal<Area[]>([]);
  protected readonly formOpen = signal(false);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly importOpen = signal(false);
  /** Credencial abierta desde una fila de la lista. */
  protected readonly credential = signal<EmployeeDetail | null>(null);
  /** Orientación de la impresión masiva, la misma que usa el preview. */
  protected readonly printOrientation = signal<CredentialOrientation>(readCredentialOrientation());
  /** Importados a los que falta oficina, turno, área o tipo. */
  protected readonly pendingSetup = signal(0);
  /** El enlace a "Organizar empleados" aparece con más de este número. */
  protected readonly organizeThreshold = 2;
  private readonly selectedOffice = signal<number | null>(null);
  private filters = { search: '', status: '' };
  private searchTimer?: ReturnType<typeof setTimeout>;

  protected readonly officeShifts = computed(() =>
    this.shifts().filter((shift) => shift.office_id === Number(this.selectedOffice())),
  );

  protected readonly form = inject(FormBuilder).nonNullable.group({
    first_name: ['', Validators.required],
    last_name: ['', Validators.required],
    position: [''],
    office_id: [0, Validators.required],
    shift_id: [0, Validators.required],
    area_id: [0, Validators.required],
    work_mode: ['onsite'],
    employment_type: ['permanent'],
    contract_ends_on: [''],
    /** Con app: se le crea usuario con este correo y él define su PIN al entrar. */
    app_access: [false],
    email: [''],
    pin: ['', [Validators.required, Validators.pattern(/^\d{6}$/)]],
    salary: [null as number | null],
    salary_period: ['biweekly'],
  });

  async ngOnInit(): Promise<void> {
    await this.load();
    if (this.canManage && this.auth.can('organization.manage')) {
      const [offices, shifts, areas] = await Promise.all([
        this.organizationService.offices(),
        this.organizationService.shifts(),
        this.organizationService.areas(),
      ]);
      this.offices.set(offices);
      this.shifts.set(shifts);
      this.areas.set(areas);
    }
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    try {
      const [page, pending] = await Promise.all([
        this.employeeService.list(this.filters),
        this.canManage ? this.employeeService.pendingSetup() : Promise.resolve({ count: 0 }),
      ]);
      this.employees.set(page.data);
      this.total.set(page.total);
      this.pendingSetup.set(pending.count);
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async onImported(result: { created: number; pending: number }): Promise<void> {
    this.importOpen.set(false);
    this.toast.success(
      `Importamos ${result.created} empleados. Ahora asígnales oficina, turno, área y tipo.`,
      { title: 'Carga masiva lista', icon: 'people' },
    );
    if (result.pending > this.organizeThreshold) {
      await this.router.navigate(['/panel/empleados/organizar']);
    } else {
      await this.load();
    }
  }

  protected search(event: Event): void {
    this.filters.search = (event.target as HTMLInputElement).value;
    clearTimeout(this.searchTimer);
    this.searchTimer = setTimeout(() => this.load(), 300);
  }

  protected filterStatus(event: Event): void {
    this.filters.status = (event.target as HTMLSelectElement).value;
    this.load();
  }

  protected open(employee: Employee): void {
    this.router.navigate(['/panel/empleados', employee.public_id]);
  }

  protected openForm(): void {
    this.error.set(null);
    const office = this.offices().find((o) => o.is_default) ?? this.offices()[0];
    this.selectedOffice.set(office?.id ?? 0);
    this.form.reset({
      office_id: office?.id ?? 0,
      shift_id: this.shifts().find((s) => s.office_id === office?.id)?.id ?? 0,
      area_id: this.areas()[0]?.id ?? 0,
      work_mode: 'onsite',
      employment_type: 'permanent',
      salary_period: 'biweekly',
      app_access: false,
    });
    this.onAppAccessChange();
    this.formOpen.set(true);
  }

  /**
   * Con app basta el correo (el PIN lo crea el empleado en su primer acceso);
   * sin app, el PIN de 6 dígitos es obligatorio para el kiosko.
   */
  protected onAppAccessChange(): void {
    const { email, pin, app_access } = this.form.controls;
    if (app_access.value) {
      email.setValidators([Validators.required, Validators.email]);
      pin.setValidators([Validators.pattern(/^\d{6}$/)]);
    } else {
      email.setValidators([Validators.email]);
      pin.setValidators([Validators.required, Validators.pattern(/^\d{6}$/)]);
    }
    email.updateValueAndValidity();
    pin.updateValueAndValidity();
  }

  protected onOfficeChange(): void {
    this.selectedOffice.set(Number(this.form.controls.office_id.value));
    this.form.controls.shift_id.setValue(this.officeShifts()[0]?.id ?? 0);
  }

  protected async save(): Promise<void> {
    this.saving.set(true);
    this.error.set(null);
    const value = this.form.getRawValue();
    const payload: Record<string, unknown> = {
      ...value,
      email: value.email.trim() || null,
      pin: value.pin || null,
      contract_ends_on:
        value.employment_type === 'temporary' ? value.contract_ends_on || null : null,
    };
    if (!this.canManagePayroll) {
      delete payload['salary'];
      delete payload['salary_period'];
    }
    try {
      const employee = await this.employeeService.create(payload);
      this.formOpen.set(false);
      this.toast.success(
        value.app_access
          ? `Le enviamos a ${value.email.trim()} su código de empresa y una contraseña temporal.`
          : 'Ya puedes imprimir su credencial.',
        { title: `${value.first_name} ${value.last_name} registrado`, icon: 'person-check-fill' },
      );
      await this.router.navigate(['/panel/empleados', employee.public_id]);
    } catch (error) {
      this.error.set(errorMessage(error));
    } finally {
      this.saving.set(false);
    }
  }

  protected async printBadges(): Promise<void> {
    const ids = this.employees()
      .filter((e) => e.status === 'active' && !e.locked_by_plan)
      .map((e) => e.id);
    try {
      await this.employeeService.downloadBadges(ids, this.printOrientation());
      this.toast.success(`${ids.length} credenciales listas para imprimir.`, { icon: 'printer' });
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected setPrintOrientation(event: Event): void {
    const value = (event.target as HTMLSelectElement).value as CredentialOrientation;
    this.printOrientation.set(value);
    saveCredentialOrientation(value);
  }

  /** Fila → credencial: se trae el detalle porque trae foto y QR. */
  protected async openCredential(employee: Employee): Promise<void> {
    try {
      this.credential.set(await this.employeeService.detail(employee.public_id));
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }
}
