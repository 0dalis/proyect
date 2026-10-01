import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';

import Toastify from 'toastify-js';

import { EmployeesService } from '../../../services/employees.service';
import { PayrollService } from '../../../services/payroll.service';
import { LoansService } from '../../../services/loans.service';
import { RequestsService } from '../../../services/requests.service';
import { AttendanceService } from '../../../services/attendance.service';
import { CredentialComponent } from '../credential/credential.component';
import { CalendarComponent } from '../../../shared/calendar/calendar.component';
import { Employee, EmployeeCompensation, PayrollConcept } from '../../../models/api.models';

@Component({
  selector: 'app-employee-detail',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, CredentialComponent, CalendarComponent],
  templateUrl: './employee-detail.component.html',
  styleUrl: './employee-detail.component.css'
})
export class EmployeeDetailComponent implements OnInit {

  tab: 'resumen' | 'calendario' | 'asistencia' | 'vacaciones' | 'prestamos' = 'resumen';

  employee: Employee | null = null;
  loading = true;
  isSubmitting = false;
  errors: any = {};

  compensation: EmployeeCompensation = this.emptyCompensation();
  labor: any = { position: '', hired_at: '', bank_name: '', bank_account: '', is_area_manager: false, is_active: true };

  concepts: PayrollConcept[] = [];
  assigned: Record<number, { selected: boolean; override: number | null }> = {};

  loans: any[] = [];
  showLoanForm = false;
  loanForm: any = this.emptyLoan();

  showCredential = false;

  // Calendario / asistencia / vacaciones
  year = new Date().getFullYear();
  calendarDays: any[] = [];
  calendarHolidays: any[] = [];
  calendarWorkDays: number[] = [1, 2, 3, 4, 5];
  attendance: any[] = [];
  balance: any = null;
  requests: any[] = [];

  constructor(
    private route: ActivatedRoute,
    private employeesService: EmployeesService,
    private payrollService: PayrollService,
    private loansService: LoansService,
    private requestsService: RequestsService,
    private attendanceService: AttendanceService
  ) {}

  ngOnInit(): void {
    this.payrollService.concepts().subscribe({ next: (res) => (this.concepts = res.concepts ?? []) });
    this.load();
  }

  setTab(tab: 'resumen' | 'calendario' | 'asistencia' | 'vacaciones' | 'prestamos'): void {
    this.tab = tab;
    if (tab === 'calendario' && this.calendarDays.length === 0) this.loadCalendar();
    if (tab === 'asistencia' && this.attendance.length === 0) this.loadAttendance();
    if (tab === 'vacaciones') this.loadVacations();
  }

  load(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.employeesService.get(id).subscribe({
      next: (res) => {
        this.employee = res.employee;
        const e: any = res.employee;
        this.labor = {
          position: e.position || '',
          hired_at: e.hired_at ? String(e.hired_at).slice(0, 10) : '',
          bank_name: e.bank_name || '',
          bank_account: e.bank_account || '',
          is_area_manager: !!e.is_area_manager,
          is_active: !!e.is_active,
        };
        if (e.compensation) {
          this.compensation = { ...this.emptyCompensation(), ...e.compensation };
        }
        this.assigned = {};
        (e.concepts || []).forEach((c: any) => {
          this.assigned[c.payroll_concept_id] = { selected: !!c.is_active, override: c.amount_override ?? null };
        });
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudo cargar el empleado.');
      }
    });
    this.loansService.list(id).subscribe({ next: (res) => (this.loans = res.loans ?? []) });
  }

  loadCalendar(): void {
    if (!this.employee) return;
    const from = `${this.year}-01-01`;
    const to = `${this.year}-12-31`;
    this.requestsService.calendar(from, to, this.employee.id).subscribe({
      next: (res) => {
        this.calendarDays = res.days ?? [];
        this.calendarHolidays = res.holidays ?? [];
      },
      error: () => {}
    });
  }

  loadAttendance(): void {
    if (!this.employee) return;
    const from = `${this.year}-01-01`;
    const to = `${this.year}-12-31`;
    this.attendanceService.list({ employee_id: this.employee.id, from, to, per_page: 100 }).subscribe({
      next: (res) => (this.attendance = res.data ?? []),
      error: () => {}
    });
  }

  loadVacations(): void {
    if (!this.employee) return;
    this.requestsService.balances(this.year).subscribe({
      next: (res) => {
        this.balance = (res.balances ?? []).find((b: any) => b.employee_id === this.employee!.id) ?? null;
      },
      error: () => {}
    });
    this.requestsService.list({ type: 'vacation' }).subscribe({
      next: (res) => (this.requests = (res.requests ?? []).filter((r: any) => r.user?.employee?.id === this.employee!.id)),
      error: () => {}
    });
  }

  timeOf(record: any, type: string): string {
    const found = (record.records || []).find((r: any) => r.type === type);
    return found ? found.recorded_at.slice(11, 16) : '—';
  }

  saveLabor(): void {
    if (!this.employee || this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};
    const e: any = this.employee;
    const payload = {
      first_name: e.first_name,
      last_name: e.last_name,
      employee_code: e.employee_code,
      office_id: e.office_id,
      area_id: e.area_id,
      shift_id: e.shift_id,
      is_area_manager: this.labor.is_area_manager,
      is_active: this.labor.is_active,
      position: this.labor.position,
      hired_at: this.labor.hired_at || null,
      bank_name: this.labor.bank_name,
      bank_account: this.labor.bank_account,
    };
    this.employeesService.update(this.employee.id, payload).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showSuccess(res.message || 'Datos guardados.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  saveCompensation(): void {
    if (!this.employee || this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};
    this.employeesService.updateCompensation(this.employee.id, this.compensation).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showSuccess(res.message || 'Compensación guardada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  saveConcepts(): void {
    if (!this.employee || this.isSubmitting) return;
    this.isSubmitting = true;
    const payload = this.concepts
      .filter((c) => this.assigned[c.id]?.selected)
      .map((c) => ({
        payroll_concept_id: c.id,
        amount_override: this.assigned[c.id]?.override ?? null,
        is_active: true,
      }));
    this.employeesService.syncConcepts(this.employee.id, payload).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showSuccess(res.message || 'Conceptos actualizados.');
      },
      error: (err) => {
        this.isSubmitting = false;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  toggleConcept(concept: PayrollConcept): void {
    if (!this.assigned[concept.id]) {
      this.assigned[concept.id] = { selected: false, override: null };
    }
    this.assigned[concept.id].selected = !this.assigned[concept.id].selected;
  }

  openLoan(): void {
    this.loanForm = this.emptyLoan();
    this.showLoanForm = true;
  }

  submitLoan(): void {
    if (!this.employee || this.isSubmitting) return;
    this.isSubmitting = true;
    this.loansService.create(this.employee.id, this.loanForm).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showLoanForm = false;
        this.loansService.list(this.employee!.id).subscribe({ next: (r) => (this.loans = r.loans ?? []) });
        this.showSuccess(res.message || 'Préstamo registrado.');
      },
      error: (err) => {
        this.isSubmitting = false;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  removeLoan(loan: any): void {
    if (!confirm(`¿Eliminar el préstamo "${loan.concept}"?`)) return;
    this.loansService.remove(loan.id).subscribe({
      next: () => {
        this.loans = this.loans.filter((l) => l.id !== loan.id);
        this.showSuccess('Préstamo eliminado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showLoanForm) this.showLoanForm = false;
  }

  private emptyCompensation(): EmployeeCompensation {
    return {
      salary_type: 'fixed', base_salary: 0, pay_frequency: 'monthly',
      daily_hours: 8, overtime_factor: 1.5, overtime_cap_minutes: null, currency: 'MXN',
    };
  }

  private emptyLoan(): any {
    return { concept: '', total_amount: 0, installments: 1, start_date: '', notes: '' };
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
