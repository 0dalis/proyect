import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CompanySetupService } from '../../services/public/company-setup.service';

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
  @Output() back = new EventEmitter<void>();

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
    has_app_access: false,
    email: '',
    password: ''
  };

  isSubmitting = false;
  errors: any = {};

  constructor(private setupService: CompanySetupService) {}

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

    const officesSub = this.setupService.getOffices();
    const areasSub = this.setupService.getAreas();
    const employeesSub = this.setupService.getEmployees();

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
      has_app_access: false, email: '', password: ''
    };
    this.errors = {};
    this.showForm = true;
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
      has_app_access: !!employee.user_id,
      email: employee.user?.email || '',
      password: ''
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

    const data: any = { ...this.form };
    if (!data.has_app_access) {
      data.email = undefined;
      data.password = undefined;
    }

    const request = this.editingId
      ? this.setupService.updateEmployee(this.editingId, data)
      : this.setupService.createEmployee(data);

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

    this.setupService.deleteEmployee(employee.id).subscribe({
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
