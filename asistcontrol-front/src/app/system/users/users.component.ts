import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { UsersService } from '../../services/users.service';
import { EmployeesService } from '../../services/employees.service';
import { Employee } from '../../models/api.models';
import { CredentialComponent } from '../employees/credential/credential.component';

@Component({
  selector: 'app-users',
  standalone: true,
  imports: [CommonModule, FormsModule, CredentialComponent],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-text-title">Usuarios y accesos</h1>
          <p class="text-sm text-text-body mt-0.5">Crea administradores y cuentas de empleado con o sin acceso al sistema</p>
        </div>
        <button (click)="openCreate()" class="btn-primary"><i class="bi bi-person-plus"></i> Nuevo usuario</button>
      </div>

      <div class="dash-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table-theme">
            <thead>
              <tr><th>Correo</th><th>Empleado</th><th>Rol</th><th>Gerente de área</th><th>Estado</th><th class="text-right">Acciones</th></tr>
            </thead>
            <tbody>
              <tr *ngFor="let u of users">
                <td class="font-semibold text-text-title">{{ u.email }}</td>
                <td>
                  <span *ngIf="u.employee">{{ u.employee.first_name }} {{ u.employee.last_name }} <span class="text-[10px] text-text-body font-mono">{{ u.employee.employee_code }}</span></span>
                  <span *ngIf="!u.employee" class="text-text-body">—</span>
                </td>
                <td>
                  <span class="badge" [ngClass]="u.roles.includes('owner') ? 'badge-warning' : (u.roles.includes('admin') ? 'badge-chip' : 'badge-slate')">
                    {{ roleLabel(u.roles) }}
                  </span>
                </td>
                <td>
                  <span *ngIf="u.employee" class="badge" [ngClass]="u.employee.is_area_manager ? 'badge-success' : 'badge-slate'">{{ u.employee.is_area_manager ? 'Sí' : 'No' }}</span>
                  <span *ngIf="!u.employee" class="text-text-body">—</span>
                </td>
                <td><span class="badge" [ngClass]="u.is_active ? 'badge-success' : 'badge-slate'">{{ u.is_active ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="text-right whitespace-nowrap">
                  <button *ngIf="u.employee" (click)="openCredential(u)" class="text-primary-medium hover:text-primary-dark text-xs mr-2" title="Credencial / QR"><i class="bi bi-qr-code"></i></button>
                  <button (click)="openEdit(u)" class="text-primary-medium hover:text-primary-dark text-xs mr-2" title="Editar"><i class="bi bi-pencil"></i></button>
                  <button (click)="openReset(u)" class="text-primary-medium hover:text-primary-dark text-xs mr-2" title="Restablecer contraseña"><i class="bi bi-key"></i></button>
                  <button *ngIf="!u.roles.includes('owner')" (click)="remove(u)" class="text-red-500 hover:text-red-700 text-xs" title="Eliminar"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
              <tr *ngIf="!loading && users.length === 0"><td colspan="6" class="py-10 text-center text-text-body">No hay usuarios.</td></tr>
              <tr *ngIf="loading"><td colspan="6" class="py-10 text-center text-text-body">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal usuario -->
    <div *ngIf="showForm" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-neutral-900/60 modal-backdrop" (click)="cancelForm()"></div>
      <div class="flex min-h-full items-center justify-center p-4" (click)="cancelForm()">
        <div class="relative w-full max-w-lg bg-surface rounded-2xl shadow-2xl modal-card border border-black/10 dark:border-white/10" (click)="$event.stopPropagation()">
          <div class="flex items-center justify-between px-6 py-4 border-b border-black/10 dark:border-white/10">
            <h2 class="text-base font-bold text-text-title">{{ editingId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
            <button (click)="cancelForm()" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-text-body hover:bg-black/5 dark:hover:bg-white/10"><i class="bi bi-x-lg text-sm"></i></button>
          </div>
          <form (ngSubmit)="submitForm()" class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="sm:col-span-2">
                <label class="form-label">Correo *</label>
                <input type="email" [(ngModel)]="form.email" name="email" required class="form-input" [ngClass]="{ 'border-red-400': errors.email }">
                <p *ngIf="errors.email" class="form-error">{{ errors.email[0] }}</p>
              </div>
              <div *ngIf="!editingId">
                <label class="form-label">Contraseña *</label>
                <input type="password" [(ngModel)]="form.password" name="password" required class="form-input" [ngClass]="{ 'border-red-400': errors.password }">
                <p *ngIf="errors.password" class="form-error">{{ errors.password[0] }}</p>
              </div>
              <div>
                <label class="form-label">Rol *</label>
                <select [(ngModel)]="form.role" name="role" class="form-input">
                  <option *ngFor="let r of roles" [value]="r.name">{{ r.label }}</option>
                </select>
              </div>
              <div *ngIf="!editingId" class="sm:col-span-2">
                <label class="form-label">Vincular a empleado (opcional)</label>
                <select [(ngModel)]="form.employee_id" name="employee_id" class="form-input">
                  <option value="">Sin vincular</option>
                  <option *ngFor="let e of linkableEmployees" [ngValue]="e.id">{{ e.first_name }} {{ e.last_name }} ({{ e.employee_code }})</option>
                </select>
                <p class="text-[10px] text-text-body mt-1">Vincula la cuenta con un empleado para que registre asistencia.</p>
              </div>
              <div class="flex items-center gap-2 pt-2 sm:col-span-2">
                <input type="checkbox" [(ngModel)]="form.is_active" name="is_active" class="w-4 h-4 rounded">
                <span class="text-xs text-text-body">Cuenta activa (con acceso al sistema)</span>
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-black/10 dark:border-white/10">
              <button type="button" (click)="cancelForm()" class="btn-secondary">Cancelar</button>
              <button type="submit" [disabled]="isSubmitting" class="btn-primary">{{ isSubmitting ? 'Guardando...' : 'Guardar' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal reset -->
    <div *ngIf="showReset" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-neutral-900/60 modal-backdrop" (click)="showReset = false"></div>
      <div class="flex min-h-full items-center justify-center p-4" (click)="showReset = false">
        <div class="relative w-full max-w-md bg-surface rounded-2xl shadow-2xl modal-card border border-black/10 dark:border-white/10" (click)="$event.stopPropagation()">
          <div class="px-6 py-4 border-b border-black/10 dark:border-white/10">
            <h2 class="text-base font-bold text-text-title">Restablecer contraseña</h2>
            <p class="text-xs text-text-body mt-0.5">{{ resetEmail }}</p>
          </div>
          <form (ngSubmit)="submitReset()" class="p-6">
            <label class="form-label">Nueva contraseña *</label>
            <input type="password" [(ngModel)]="newPassword" name="newPassword" required minlength="8" class="form-input" [ngClass]="{ 'border-red-400': errors.password }">
            <p *ngIf="errors.password" class="form-error">{{ errors.password[0] }}</p>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-black/10 dark:border-white/10">
              <button type="button" (click)="showReset = false" class="btn-secondary">Cancelar</button>
              <button type="submit" [disabled]="isSubmitting" class="btn-primary">Restablecer</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <app-credential *ngIf="credentialEmployeeId" [employeeId]="credentialEmployeeId" (close)="credentialEmployeeId = null"></app-credential>
  `,
  styles: [':host { display: block; }']
})
export class UsersComponent implements OnInit {

  users: any[] = [];
  employees: Employee[] = [];
  roles: { name: string; label: string }[] = [];
  loading = true;

  showForm = false;
  editingId: number | null = null;
  isSubmitting = false;
  errors: any = {};
  form: any = this.emptyForm();

  showReset = false;
  resetId: number | null = null;
  resetEmail = '';
  newPassword = '';

  credentialEmployeeId: number | null = null;

  constructor(
    private usersService: UsersService,
    private employeesService: EmployeesService
  ) {}

  ngOnInit(): void {
    this.load();
    this.usersService.roles().subscribe({ next: (res) => (this.roles = res.roles) });
    this.employeesService.list().subscribe({ next: (res) => (this.employees = res.employees ?? []) });
  }

  get linkableEmployees(): Employee[] {
    return this.employees.filter((e: any) => !e.user_id);
  }

  openCredential(user: any): void {
    if (user?.employee?.id) {
      this.credentialEmployeeId = user.employee.id;
    }
  }

  load(): void {
    this.loading = true;
    this.usersService.list().subscribe({
      next: (res) => {
        this.users = res.users ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar los usuarios.');
      }
    });
  }

  openCreate(): void {
    this.editingId = null;
    this.form = this.emptyForm();
    this.errors = {};
    this.showForm = true;
  }

  openEdit(user: any): void {
    this.editingId = user.id;
    this.form = {
      email: user.email,
      role: user.roles.includes('admin') ? 'admin' : 'employee',
      is_active: user.is_active,
    };
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

    const payload: any = { email: this.form.email, role: this.form.role, is_active: this.form.is_active };
    if (!this.editingId) {
      payload.password = this.form.password;
      if (this.form.employee_id) payload.employee_id = this.form.employee_id;
    }

    const request = this.editingId
      ? this.usersService.update(this.editingId, payload)
      : this.usersService.create(payload);

    request.subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showForm = false;
        this.load();
        this.employeesService.list().subscribe({ next: (r) => (this.employees = r.employees ?? []) });
        this.showSuccess(res.message || 'Usuario guardado.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  openReset(user: any): void {
    this.resetId = user.id;
    this.resetEmail = user.email;
    this.newPassword = '';
    this.errors = {};
    this.showReset = true;
  }

  submitReset(): void {
    if (!this.resetId || this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};
    this.usersService.resetPassword(this.resetId, this.newPassword).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showReset = false;
        this.showSuccess(res.message || 'Contraseña restablecida.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error.');
      }
    });
  }

  remove(user: any): void {
    if (!confirm(`¿Eliminar el usuario ${user.email}?`)) return;
    this.usersService.remove(user.id).subscribe({
      next: () => {
        this.load();
        this.showSuccess('Usuario eliminado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  roleLabel(roles: string[]): string {
    if (roles.includes('owner')) return 'Propietario';
    if (roles.includes('admin')) return 'Administrador';
    if (roles.includes('employee')) return 'Empleado';
    return roles[0] || '—';
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showForm) this.cancelForm();
    if (this.showReset) this.showReset = false;
  }

  private emptyForm(): any {
    return { email: '', password: '', role: 'employee', employee_id: '', is_active: true };
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
