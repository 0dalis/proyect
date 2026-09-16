import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { AttendanceService } from '../../services/attendance.service';
import { EmployeesService } from '../../services/employees.service';
import { CatalogService } from '../../services/catalog.service';
import { ExportButtonComponent } from '../../shared/export-button/export-button.component';
import { Attendance, Employee, Office } from '../../models/api.models';

@Component({
  selector: 'app-attendance',
  standalone: true,
  imports: [CommonModule, FormsModule, ExportButtonComponent],
  templateUrl: './attendance.component.html',
  styleUrl: './attendance.component.css'
})
export class AttendanceComponent implements OnInit {

  records: Attendance[] = [];
  offices: Office[] = [];
  employees: Employee[] = [];
  loading = true;

  filters = {
    from: this.firstOfMonth(),
    to: this.today(),
    office_id: '',
    employee_id: '',
    status: '',
    page: 1,
    per_page: 25,
  };
  total = 0;
  lastPage = 1;

  showForm = false;
  isSubmitting = false;
  errors: any = {};
  form: any = this.emptyForm();

  constructor(
    private attendanceService: AttendanceService,
    private employeesService: EmployeesService,
    private catalogService: CatalogService
  ) {}

  ngOnInit(): void {
    this.loadOffices();
    this.loadEmployees();
    this.load();
  }

  get exportFilters(): Record<string, any> {
    return {
      from: this.filters.from,
      to: this.filters.to,
      office_id: this.filters.office_id,
      status: this.filters.status,
    };
  }

  load(): void {
    this.loading = true;
    this.attendanceService.list(this.filters).subscribe({
      next: (res) => {
        this.records = res.data ?? [];
        this.total = res.total ?? 0;
        this.lastPage = res.last_page ?? 1;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar los registros.');
      }
    });
  }

  loadOffices(): void {
    this.catalogService.offices().subscribe({
      next: (res) => (this.offices = res.offices ?? []),
      error: () => {}
    });
  }

  loadEmployees(): void {
    this.employeesService.list().subscribe({
      next: (res) => (this.employees = res.employees ?? []),
      error: () => {}
    });
  }

  applyFilters(): void {
    this.filters.page = 1;
    this.load();
  }

  changePage(delta: number): void {
    const next = this.filters.page + delta;
    if (next < 1 || next > this.lastPage) return;
    this.filters.page = next;
    this.load();
  }

  openCorrect(record?: Attendance): void {
    this.errors = {};
    if (record) {
      this.form = {
        employee_id: record.employee_id,
        date: record.date?.slice(0, 10),
        status: record.status,
        reason: '',
        check_in: this.timeOf(record, 'check_in'),
        check_out: this.timeOf(record, 'check_out'),
        lunch_start: this.timeOf(record, 'lunch_start'),
        lunch_end: this.timeOf(record, 'lunch_end'),
      };
    } else {
      this.form = this.emptyForm();
    }
    this.showForm = true;
  }

  cancelForm(): void {
    this.showForm = false;
    this.errors = {};
  }

  submitForm(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};

    const records: any[] = [];
    const base = this.form.date;
    if (this.form.check_in) records.push({ type: 'check_in', recorded_at: `${base} ${this.form.check_in}:00` });
    if (this.form.check_out) records.push({ type: 'check_out', recorded_at: `${base} ${this.form.check_out}:00` });
    if (this.form.lunch_start) records.push({ type: 'lunch_start', recorded_at: `${base} ${this.form.lunch_start}:00` });
    if (this.form.lunch_end) records.push({ type: 'lunch_end', recorded_at: `${base} ${this.form.lunch_end}:00` });

    const payload = {
      employee_id: this.form.employee_id,
      date: this.form.date,
      status: this.form.status || 'present',
      reason: this.form.reason,
      records,
    };

    this.attendanceService.correct(payload).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showForm = false;
        this.load();
        this.showSuccess(res.message || 'Asistencia guardada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  timeOf(record: Attendance, type: string): string {
    const found = (record.records || []).find((r) => r.type === type);
    return found ? found.recorded_at.slice(11, 16) : '';
  }

  statusBadge(status: string): string {
    return {
      present: 'badge-success',
      late: 'badge-warning',
      absent: 'badge-danger',
      justified: 'badge-slate',
    }[status] ?? 'badge-slate';
  }

  statusLabel(status: string): string {
    return {
      present: 'Presente',
      late: 'Retardo',
      absent: 'Falta',
      justified: 'Justificado',
    }[status] ?? status;
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showForm) this.cancelForm();
  }

  private emptyForm(): any {
    return {
      employee_id: '',
      date: this.today(),
      status: 'present',
      reason: '',
      check_in: '08:00',
      check_out: '16:00',
      lunch_start: '',
      lunch_end: '',
    };
  }

  private today(): string {
    return new Date().toISOString().slice(0, 10);
  }

  private firstOfMonth(): string {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
