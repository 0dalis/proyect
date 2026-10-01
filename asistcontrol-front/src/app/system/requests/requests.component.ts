import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { RequestsService } from '../../services/requests.service';
import { EmployeesService } from '../../services/employees.service';
import { ExportButtonComponent } from '../../shared/export-button/export-button.component';
import { Employee, WorkRequest } from '../../models/api.models';

@Component({
  selector: 'app-requests',
  standalone: true,
  imports: [CommonModule, FormsModule, ExportButtonComponent],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-text-title">Solicitudes</h1>
          <p class="text-sm text-text-body mt-0.5">Permisos, justificaciones y vacaciones</p>
        </div>
        <div class="flex items-center gap-2">
          <app-export-button resource="requests"></app-export-button>
          <button (click)="openCreate()" class="btn-primary"><i class="bi bi-plus-lg"></i> Nueva solicitud</button>
        </div>
      </div>

      <div class="dash-card p-4">
        <div class="flex gap-2 flex-wrap">
          <button (click)="setFilter('')" class="btn-secondary" [ngClass]="status === '' ? 'border-primary-medium text-primary-medium' : ''">Todas</button>
          <button (click)="setFilter('pending')" class="btn-secondary" [ngClass]="status === 'pending' ? 'border-primary-medium text-primary-medium' : ''">Pendientes</button>
          <button (click)="setFilter('approved')" class="btn-secondary" [ngClass]="status === 'approved' ? 'border-primary-medium text-primary-medium' : ''">Aprobadas</button>
          <button (click)="setFilter('rejected')" class="btn-secondary" [ngClass]="status === 'rejected' ? 'border-primary-medium text-primary-medium' : ''">Rechazadas</button>
        </div>
      </div>

      <div class="dash-card overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-black/10 dark:border-white/10">
          <h2 class="text-sm font-semibold text-text-title">Saldos de vacaciones</h2>
          <div class="flex items-center gap-2 text-xs text-text-body">
            Año
            <input type="number" [(ngModel)]="balanceYear" (change)="loadBalances()" class="form-input" style="max-width: 100px;">
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="table-theme">
            <thead><tr><th>Empleado</th><th>Días al año</th><th>Usados</th><th>Disponibles</th><th class="text-right">Acción</th></tr></thead>
            <tbody>
              <tr *ngFor="let b of balances">
                <td class="font-semibold text-text-title">{{ b.employee_name }} <span class="text-[10px] text-text-body font-mono">{{ b.employee_code }}</span></td>
                <td><input type="number" min="0" [(ngModel)]="b.days_entitled" class="form-input" style="max-width: 90px;"></td>
                <td><input type="number" min="0" [(ngModel)]="b.days_used" class="form-input" style="max-width: 90px;"></td>
                <td class="font-bold text-success-green">{{ b.days_available }}</td>
                <td class="text-right"><button (click)="saveBalance(b)" class="btn-secondary">Guardar</button></td>
              </tr>
              <tr *ngIf="balances.length === 0"><td colspan="5" class="py-8 text-center text-text-body text-xs">Sin empleados.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="dash-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table-theme">
            <thead>
              <tr><th>Empleado</th><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Goce</th><th>Estado</th><th class="text-right">Acciones</th></tr>
            </thead>
            <tbody>
              <tr *ngFor="let r of requests">
                <td class="font-semibold text-text-title">
                  {{ r.user?.employee?.first_name }} {{ r.user?.employee?.last_name }}
                  <span class="block text-[10px] text-text-body font-normal">{{ r.user?.email }}</span>
                </td>
                <td>{{ typeLabel(r.type) }}</td>
                <td>{{ r.start_date | date: 'dd/MM/yyyy' }}</td>
                <td>{{ r.end_date ? (r.end_date | date: 'dd/MM/yyyy') : '—' }}</td>
                <td>{{ r.is_paid ? 'Con goce' : 'Sin goce' }}</td>
                <td><span class="badge" [ngClass]="statusBadge(r.status)">{{ statusLabel(r.status) }}</span></td>
                <td class="text-right whitespace-nowrap">
                  <button *ngIf="r.status === 'pending'" (click)="approve(r)" class="text-success-green text-xs mr-2" title="Aprobar"><i class="bi bi-check-lg"></i></button>
                  <button *ngIf="r.status === 'pending'" (click)="reject(r)" class="text-red-500 text-xs" title="Rechazar"><i class="bi bi-x-lg"></i></button>
                </td>
              </tr>
              <tr *ngIf="!loading && requests.length === 0">
                <td colspan="7" class="py-10 text-center text-text-body"><i class="bi bi-inbox text-2xl"></i><p class="mt-2 text-xs">No hay solicitudes.</p></td>
              </tr>
              <tr *ngIf="loading"><td colspan="7" class="py-10 text-center text-text-body">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div *ngIf="showForm" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-neutral-900/60 modal-backdrop" (click)="cancelForm()"></div>
      <div class="flex min-h-full items-center justify-center p-4" (click)="cancelForm()">
        <div class="relative w-full max-w-lg bg-surface rounded-2xl shadow-2xl modal-card border border-black/10 dark:border-white/10" (click)="$event.stopPropagation()">
          <div class="flex items-center justify-between px-6 py-4 border-b border-black/10 dark:border-white/10">
            <h2 class="text-base font-bold text-text-title">Nueva solicitud</h2>
            <button (click)="cancelForm()" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-text-body hover:bg-black/5 dark:hover:bg-white/10"><i class="bi bi-x-lg text-sm"></i></button>
          </div>
          <form (ngSubmit)="submitForm()" class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="sm:col-span-2">
                <label class="form-label">Empleado *</label>
                <select [(ngModel)]="form.employee_id" name="employee_id" required class="form-input" [ngClass]="{ 'border-red-400': errors.employee_id }">
                  <option value="" disabled>Seleccionar...</option>
                  <option *ngFor="let e of employees" [ngValue]="e.id">{{ e.first_name }} {{ e.last_name }} ({{ e.employee_code }})</option>
                </select>
                <p *ngIf="errors.employee_id" class="form-error">{{ errors.employee_id[0] }}</p>
              </div>
              <div>
                <label class="form-label">Tipo *</label>
                <select [(ngModel)]="form.type" name="type" class="form-input">
                  <option value="permission">Permiso</option>
                  <option value="justification">Justificación</option>
                  <option value="vacation">Vacaciones</option>
                </select>
              </div>
              <div class="flex items-center gap-2 pt-6">
                <input type="checkbox" [(ngModel)]="form.is_paid" name="is_paid" class="w-4 h-4 rounded">
                <span class="text-xs text-text-body">Con goce de sueldo</span>
              </div>
              <div>
                <label class="form-label">Inicio *</label>
                <input type="date" [(ngModel)]="form.start_date" name="start_date" required class="form-input" [ngClass]="{ 'border-red-400': errors.start_date }">
                <p *ngIf="errors.start_date" class="form-error">{{ errors.start_date[0] }}</p>
              </div>
              <div>
                <label class="form-label">Fin</label>
                <input type="date" [(ngModel)]="form.end_date" name="end_date" class="form-input">
              </div>
              <div class="sm:col-span-2">
                <label class="form-label">Motivo</label>
                <textarea [(ngModel)]="form.reason" name="reason" rows="3" class="form-input"></textarea>
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-black/10 dark:border-white/10">
              <button type="button" (click)="cancelForm()" class="btn-secondary">Cancelar</button>
              <button type="submit" [disabled]="isSubmitting" class="btn-primary">{{ isSubmitting ? 'Guardando...' : 'Crear' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `,
  styles: [':host { display: block; }']
})
export class RequestsComponent implements OnInit {

  requests: WorkRequest[] = [];
  employees: Employee[] = [];
  loading = true;
  status = '';
  showForm = false;
  isSubmitting = false;
  errors: any = {};
  form: any = this.emptyForm();

  balances: any[] = [];
  balanceYear = new Date().getFullYear();
  balanceEdit: any = {};

  constructor(
    private requestsService: RequestsService,
    private employeesService: EmployeesService
  ) {}

  ngOnInit(): void {
    this.load();
    this.employeesService.list().subscribe({ next: (res) => (this.employees = res.employees ?? []) });
    this.loadBalances();
  }

  loadBalances(): void {
    this.requestsService.balances(this.balanceYear).subscribe({
      next: (res) => (this.balances = res.balances ?? []),
      error: () => {}
    });
  }

  saveBalance(balance: any): void {
    this.requestsService.updateBalance(balance.employee_id, {
      year: this.balanceYear,
      days_entitled: balance.days_entitled,
      days_used: balance.days_used,
    }).subscribe({
      next: (res) => {
        balance.days_available = res.balance.days_entitled - res.balance.days_used;
        this.showSuccess('Saldo actualizado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al guardar saldo.')
    });
  }

  load(): void {
    this.loading = true;
    this.requestsService.list({ status: this.status }).subscribe({
      next: (res) => {
        this.requests = res.requests ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar las solicitudes.');
      }
    });
  }

  setFilter(status: string): void {
    this.status = status;
    this.load();
  }

  openCreate(): void {
    this.form = this.emptyForm();
    this.errors = {};
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
    this.requestsService.create(this.form).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showForm = false;
        this.load();
        this.showSuccess(res.message || 'Solicitud creada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al crear.');
      }
    });
  }

  approve(request: WorkRequest): void {
    this.requestsService.approve(request.id).subscribe({
      next: (res) => {
        this.load();
        this.showSuccess(res.message || 'Solicitud aprobada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error.')
    });
  }

  reject(request: WorkRequest): void {
    this.requestsService.reject(request.id).subscribe({
      next: (res) => {
        this.load();
        this.showSuccess(res.message || 'Solicitud rechazada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error.')
    });
  }

  typeLabel(type: string): string {
    return { permission: 'Permiso', justification: 'Justificación', vacation: 'Vacaciones' }[type] ?? type;
  }

  statusBadge(status: string): string {
    return { pending: 'badge-warning', approved: 'badge-success', rejected: 'badge-danger' }[status] ?? 'badge-slate';
  }

  statusLabel(status: string): string {
    return { pending: 'Pendiente', approved: 'Aprobada', rejected: 'Rechazada' }[status] ?? status;
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showForm) this.cancelForm();
  }

  private emptyForm(): any {
    return { employee_id: '', type: 'permission', start_date: '', end_date: '', reason: '', is_paid: false };
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
