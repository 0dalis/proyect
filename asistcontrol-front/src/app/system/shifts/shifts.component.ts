import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ShiftService } from '../../services/shift.services';

import Toastify from 'toastify-js';

@Component({
  selector: 'app-shifts',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './shifts.component.html',
  styleUrl: './shifts.component.css'
})
export class ShiftsComponent implements OnInit {

  offices: any[] = [];
  employees: any[] = [];
  loading = true;
  error = '';

  selectedOfficeId: number | null = null;

  showForm = false;
  editingId: number | null = null;
  form = {
    office_id: null as number | null,
    name: '',
    start_time: '08:00',
    end_time: '16:00',
    cross_midnight: false,
    lunch_start: '',
    lunch_end: '',
    tolerance_minutes: 10,
    early_leave_minutes: 0,
    work_hours_expected: null as number | null,
    is_active: true
  };
  isSubmitting = false;
  errors: any = {};

  constructor(private shiftService: ShiftService) {}

  ngOnInit(): void {
    this.load();
  }

  get selectedOffice(): any {
    return this.offices.find(o => o.id === this.selectedOfficeId) ?? null;
  }

  get shifts(): any[] {
    return this.selectedOffice?.shifts ?? [];
  }

  get officeEmployees(): any[] {
    if (!this.selectedOfficeId) return [];
    return this.employees.filter(e => e.office_id === this.selectedOfficeId);
  }

  load(): void {
    this.loading = true;
    this.error = '';

    this.shiftService.getShifts().subscribe({
      next: (res: any) => {
        this.offices = res.offices ?? [];
        this.employees = res.employees ?? [];
        if (!this.selectedOfficeId && this.offices.length > 0) {
          this.selectedOfficeId = this.offices[0].id;
        }
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.error = 'No se pudieron cargar los horarios.';
      }
    });
  }

  selectOffice(id: number): void {
    this.selectedOfficeId = id;
    this.cancelForm();
  }

  officeShifts(officeId: number): any[] {
    return this.offices.find(o => o.id === officeId)?.shifts ?? [];
  }

  shiftName(shiftId: number | null): string {
    if (!shiftId) return 'Sin asignar';
    const all = this.offices.flatMap(o => o.shifts);
    return all.find(s => s.id === shiftId)?.name ?? 'Sin asignar';
  }

  openCreate(): void {
    this.editingId = null;
    this.form = {
      office_id: this.selectedOfficeId,
      name: '', start_time: '08:00', end_time: '16:00', cross_midnight: false,
      lunch_start: '', lunch_end: '', tolerance_minutes: 10, early_leave_minutes: 0,
      work_hours_expected: null, is_active: true
    };
    this.errors = {};
    this.showForm = true;
  }

  openEdit(shift: any): void {
    this.editingId = shift.id;
    this.form = {
      office_id: shift.office_id,
      name: shift.name,
      start_time: shift.start_time,
      end_time: shift.end_time,
      cross_midnight: !!shift.cross_midnight,
      lunch_start: shift.lunch_start || '',
      lunch_end: shift.lunch_end || '',
      tolerance_minutes: shift.tolerance_minutes,
      early_leave_minutes: shift.early_leave_minutes,
      work_hours_expected: shift.work_hours_expected,
      is_active: !!shift.is_active
    };
    this.errors = {};
    this.showForm = true;
  }

  cancelForm(): void {
    this.showForm = false;
    this.editingId = null;
    this.errors = {};
  }

  submitForm(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};

    const data: any = { ...this.form };
    data.lunch_start = data.lunch_start || null;
    data.lunch_end = data.lunch_end || null;
    data.work_hours_expected = data.work_hours_expected ?? null;

    const request = this.editingId
      ? this.shiftService.updateShift(this.editingId, data)
      : this.shiftService.createShift({ ...data, office_id: this.form.office_id ?? this.selectedOfficeId });

    request.subscribe({
      next: (res: any) => {
        this.showForm = false;
        this.editingId = null;
        this.isSubmitting = false;
        this.load();
        this.showSuccess(res.message);
      },
      error: (err: any) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar el turno.');
      }
    });
  }

  deleteShift(shift: any): void {
    if (!confirm(`¿Eliminar el turno "${shift.name}"? Los empleados asignados quedarán sin turno.`)) return;
    this.shiftService.deleteShift(shift.id).subscribe({
      next: (res: any) => {
        this.load();
        this.showSuccess(res.message);
      },
      error: (err: any) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  onAssign(employee: any, shiftId: any): void {
    this.shiftService.assignShift({ employee_id: employee.id, shift_id: shiftId || null }).subscribe({
      next: (res: any) => this.showSuccess(res.message),
      error: (err: any) => this.showError(err.error?.message || 'Error al asignar el turno.')
    });
  }

  @HostListener('window:keydown.escape', ['$event'])
  onEscape(event: KeyboardEvent): void {
    if (this.showForm) this.cancelForm();
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
