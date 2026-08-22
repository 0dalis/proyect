import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CompleteProfileService } from '../../services/public/completeprofile.services';

import Toastify from 'toastify-js';

@Component({
  selector: 'app-step4-employees',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './step4-employees.component.html',
  styleUrl: './step4-employees.component.css'
})
export class Step4EmployeesComponent implements OnInit {

  @Input() limits: any = null;
  @Input() completing = false;
  @Output() back = new EventEmitter<void>();
  @Output() finish = new EventEmitter<void>();

  employees: any[] = [];
  offices: any[] = [];
  areas: any[] = [];
  isLoading = true;
  showForm = false;
  editingId: number | null = null;

  form = {
    first_name: '',
    last_name: '',
    employee_code: '',
    office_id: null as number | null,
    area_id: null as number | null,
    is_area_manager: false,
    has_app_access: false,
    email: ''
  };

  isSubmitting = false;
  errors: any = {};

  constructor(private completeProfileService: CompleteProfileService) {}

  get employeeLimit(): number | null {
    return this.limits?.employee_limit ?? null;
  }

  get canCreate(): boolean {
    return this.limits?.can_create_employee ?? true;
  }

  get overLimitMessage(): string {
    const limit = this.employeeLimit;
    if (limit === null) return '';
    return `Tu plan permite hasta ${limit} empleados. Los que excedan el límite solo pueden eliminarse y no podrán registrar asistencia.`;
  }

  get selectedAreaName(): string {
    const area = this.areas.find((a) => a.id === this.form.area_id);
    return area ? area.name : '';
  }

  get hasAreas(): boolean {
    return this.areas.length > 0;
  }

  get selectedAreaHasManager(): boolean {
    if (!this.form.area_id) return false;
    return this.employees.some(
      (e) => e.is_area_manager && e.area_id === this.form.area_id && e.id !== this.editingId
    );
  }

  get canBeAreaManager(): boolean {
    return !!this.form.area_id && !this.selectedAreaHasManager;
  }

  isLocked(employee: any): boolean {
    const limit = this.employeeLimit;
    if (limit === null) return false;
    return this.employees.findIndex((e) => e.id === employee.id) >= limit;
  }

  ngOnInit(): void {
    this.loadAll();
  }

  loadAll(): void {
    this.isLoading = true;

    const officesSub = this.completeProfileService.getOffices();
    const areasSub = this.completeProfileService.getAreas();
    const employeesSub = this.completeProfileService.getEmployees();

    officesSub.subscribe({ next: (res: any) => this.offices = res.offices, error: () => {} });
    areasSub.subscribe({ next: (res: any) => this.areas = res.areas, error: () => {} });
    employeesSub.subscribe({
      next: (res: any) => {
        this.employees = res.employees;
        this.isLoading = false;
      },
      error: () => {
        this.isLoading = false;
        this.showError('Error al cargar empleados.');
      }
    });
  }

  openCreate(): void {
    this.editingId = null;
    this.form = {
      first_name: '', last_name: '', employee_code: '',
      office_id: this.offices.length > 0 ? this.offices[0].id : null,
      area_id: this.areas.length > 0 ? this.areas[0].id : null,
      is_area_manager: false,
      has_app_access: false, email: ''
    };
    this.errors = {};
    this.showForm = true;
    this.generateEmployeeCode();
  }

  private generateEmployeeCode(): void {
    this.completeProfileService.generateEmployeeCode().subscribe({
      next: (res: any) => {
        this.form.employee_code = res.code;
      },
      error: () => {}
    });
  }

  openEdit(employee: any): void {
    if (this.isLocked(employee)) {
      this.showError('Este empleado excede el límite de tu plan. Solo puedes eliminarlo.');
      return;
    }
    this.editingId = employee.id;
    this.form = {
      first_name: employee.first_name,
      last_name: employee.last_name,
      employee_code: employee.employee_code,
      office_id: employee.office_id,
      area_id: employee.area_id,
      is_area_manager: !!employee.is_area_manager,
      has_app_access: !!employee.user_id,
      email: employee.user?.email || ''
    };
    this.errors = {};
    this.showForm = true;
  }

  cancelForm(): void {
    this.showForm = false;
    this.editingId = null;
  }

  submitForm(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};

    if (!this.editingId && !this.canCreate) {
      this.isSubmitting = false;
      this.showError(this.overLimitMessage);
      return;
    }

    if (this.form.is_area_manager && !this.form.area_id) {
      this.isSubmitting = false;
      this.showError('Para asignar un gerente de área primero debes seleccionar un área.');
      return;
    }

    if (this.form.is_area_manager && this.selectedAreaHasManager) {
      this.isSubmitting = false;
      this.showError('El área seleccionada ya tiene un gerente asignado.');
      return;
    }

    const data: any = { ...this.form };
    if (!data.has_app_access) {
      data.email = undefined;
    }

    const request = this.editingId
      ? this.completeProfileService.updateEmployee(this.editingId, data)
      : this.completeProfileService.createEmployee(data);

    request.subscribe({
      next: () => {
        this.showForm = false;
        this.editingId = null;
        this.isSubmitting = false;
        this.loadAll();
        this.showSuccess('Empleado guardado.');
      },
      error: (err: any) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError('Error al guardar.');
      }
    });
  }

  deleteEmployee(employee: any): void {
    if (!confirm(`¿Eliminar a "${employee.first_name} ${employee.last_name}"?`)) return;

    this.completeProfileService.deleteEmployee(employee.id).subscribe({
      next: () => {
        this.loadAll();
        this.showSuccess('Empleado eliminado.');
      },
      error: (err: any) => {
        this.showError(err.error?.message || 'Error al eliminar.');
      }
    });
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
