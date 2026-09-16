import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

import Toastify from 'toastify-js';

import { EmployeesService } from '../../services/employees.service';
import { CatalogService } from '../../services/catalog.service';
import { ShiftService } from '../../services/shift.services';
import { NotificationsService } from '../../services/notifications.service';
import { ExportButtonComponent } from '../../shared/export-button/export-button.component';
import { Area, Employee, Office } from '../../models/api.models';

@Component({
  selector: 'app-employees',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, ExportButtonComponent],
  templateUrl: './employees.component.html',
  styleUrl: './employees.component.css'
})
export class EmployeesComponent implements OnInit {

  tab: 'employees' | 'notifications' = 'employees';

  employees: Employee[] = [];
  offices: Office[] = [];
  areas: Area[] = [];
  shifts: any[] = [];
  loading = true;
  search = '';

  // Notificaciones
  recipients: any[] = [];
  recipientsSummary: any = { total: 0, no_access: 0, access_not_installed: 0, available: 0 };
  recipientsLoading = false;
  recipientStatus = '';
  recipientAreaId: any = '';
  recipientOfficeId: any = '';
  recipientSearch = '';

  notifications: any[] = [];
  notifForm: any = this.emptyNotification();
  notifErrors: any = {};
  notifSubmitting = false;
  previewResult: any = null;
  selectedUserIds: number[] = [];

  showForm = false;
  editingId: number | null = null;
  isSubmitting = false;
  errors: any = {};
  form: any = this.emptyForm();

  constructor(
    private employeesService: EmployeesService,
    private catalogService: CatalogService,
    private shiftService: ShiftService,
    private notificationsService: NotificationsService
  ) {}

  ngOnInit(): void {
    this.load();
    this.loadCatalogs();
  }

  setTab(tab: 'employees' | 'notifications'): void {
    this.tab = tab;
    if (tab === 'notifications') {
      this.loadRecipients();
      this.loadNotifications();
    }
  }

  load(): void {
    this.loading = true;
    this.employeesService.list({ search: this.search }).subscribe({
      next: (res) => {
        this.employees = res.employees ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar los empleados.');
      }
    });
  }

  loadCatalogs(): void {
    this.catalogService.offices().subscribe({ next: (res) => (this.offices = res.offices ?? []) });
    this.catalogService.areas().subscribe({ next: (res) => (this.areas = res.areas ?? []) });
    this.shiftService.getShifts().subscribe({ next: (res) => (this.shifts = res.offices ?? []) });
  }

  get shiftsForOffice(): any[] {
    const office = this.shifts.find((o) => o.id === this.form.office_id);
    return office?.shifts ?? [];
  }

  openCreate(): void {
    this.editingId = null;
    this.form = this.emptyForm();
    this.errors = {};
    this.showForm = true;
    this.generateCode();
  }

  openEdit(employee: Employee): void {
    this.editingId = employee.id;
    this.form = {
      first_name: employee.first_name,
      last_name: employee.last_name,
      employee_code: employee.employee_code,
      office_id: employee.office_id,
      area_id: employee.area_id ?? '',
      shift_id: employee.shift_id ?? '',
      is_area_manager: employee.is_area_manager,
      is_active: employee.is_active,
      pin: '',
    };
    this.errors = {};
    this.showForm = true;
  }

  generateCode(): void {
    this.employeesService.generateCode().subscribe({
      next: (res) => (this.form.employee_code = res.code),
      error: () => {}
    });
  }

  cancelForm(): void {
    this.showForm = false;
    this.errors = {};
  }

  submitForm(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};

    const payload: any = {
      first_name: this.form.first_name,
      last_name: this.form.last_name,
      employee_code: this.form.employee_code,
      office_id: this.form.office_id,
      area_id: this.form.area_id || null,
      shift_id: this.form.shift_id || null,
      is_area_manager: !!this.form.is_area_manager,
      is_active: !!this.form.is_active,
    };
    if (this.form.pin) payload.pin = this.form.pin;

    const request = this.editingId
      ? this.employeesService.update(this.editingId, payload)
      : this.employeesService.create(payload);

    request.subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showForm = false;
        this.load();
        this.showSuccess(res.message || 'Empleado guardado.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  remove(employee: Employee): void {
    if (!confirm(`¿Eliminar a ${employee.first_name} ${employee.last_name}?`)) return;
    this.employeesService.remove(employee.id).subscribe({
      next: () => {
        this.load();
        this.showSuccess('Empleado eliminado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showForm) this.cancelForm();
  }

  // ---------------- Notificaciones ----------------

  get filteredRecipients(): any[] {
    const term = this.recipientSearch.trim().toLowerCase();
    return this.recipients.filter((r) => {
      if (this.recipientStatus && r.status !== this.recipientStatus) return false;
      if (this.recipientAreaId && r.area !== this.recipientAreaId) return false;
      if (this.recipientOfficeId && r.office !== this.recipientOfficeId) return false;
      if (term) {
        const haystack = `${r.full_name} ${r.employee_code} ${r.email ?? ''}`.toLowerCase();
        if (!haystack.includes(term)) return false;
      }
      return true;
    });
  }

  loadRecipients(): void {
    this.recipientsLoading = true;
    this.notificationsService.recipients().subscribe({
      next: (res) => {
        this.recipients = res.recipients ?? [];
        this.recipientsSummary = res.summary ?? this.recipientsSummary;
        this.recipientsLoading = false;
      },
      error: () => {
        this.recipientsLoading = false;
        this.showError('No se pudieron cargar los destinatarios.');
      }
    });
  }

  loadNotifications(): void {
    this.notificationsService.list().subscribe({
      next: (res) => (this.notifications = res.notifications ?? []),
      error: () => {}
    });
  }

  isSelected(userId: number): boolean {
    return this.selectedUserIds.includes(userId);
  }

  toggleRecipient(recipient: any): void {
    if (!recipient.user_id || recipient.status === 'no_access') return;
    const idx = this.selectedUserIds.indexOf(recipient.user_id);
    if (idx >= 0) {
      this.selectedUserIds.splice(idx, 1);
    } else {
      this.selectedUserIds.push(recipient.user_id);
    }
  }

  selectAllAvailable(): void {
    this.selectedUserIds = this.recipients.filter((r) => r.status === 'available').map((r) => r.user_id);
  }

  clearSelection(): void {
    this.selectedUserIds = [];
  }

  onTargetChange(): void {
    this.previewResult = null;
    if (this.notifForm.target_type !== 'users') {
      this.selectedUserIds = [];
    }
  }

  preview(): void {
    this.previewResult = null;
    const payload: any = { target_type: this.notifForm.target_type };
    if (this.notifForm.target_type === 'area') payload.area_id = this.notifForm.area_id;
    if (this.notifForm.target_type === 'office') payload.office_id = this.notifForm.office_id;
    if (this.notifForm.target_type === 'user') payload.target_user_id = this.notifForm.target_user_id;
    if (this.notifForm.target_type === 'users') payload.target_user_ids = this.selectedUserIds;

    this.notificationsService.preview(payload).subscribe({
      next: (res) => (this.previewResult = res),
      error: (err) => this.showError(err.error?.message || 'No se pudo previsualizar.')
    });
  }

  sendNotification(): void {
    if (this.notifSubmitting) return;
    this.notifErrors = {};

    if (!this.notifForm.title || !this.notifForm.message) {
      this.notifErrors = { title: ['Título y mensaje son obligatorios.'] };
      return;
    }
    if (this.notifForm.target_type === 'users' && this.selectedUserIds.length === 0) {
      this.showError('Selecciona al menos un usuario.');
      return;
    }

    this.notifSubmitting = true;
    const payload: any = {
      title: this.notifForm.title,
      message: this.notifForm.message,
      priority: this.notifForm.priority,
      target_type: this.notifForm.target_type,
    };
    if (this.notifForm.target_type === 'area') payload.area_id = this.notifForm.area_id;
    if (this.notifForm.target_type === 'office') payload.office_id = this.notifForm.office_id;
    if (this.notifForm.target_type === 'user') payload.target_user_id = this.notifForm.target_user_id;
    if (this.notifForm.target_type === 'users') payload.target_user_ids = this.selectedUserIds;

    this.notificationsService.create(payload).subscribe({
      next: (res) => {
        this.notificationsService.send(res.notification.id).subscribe({
          next: (sendRes) => {
            this.notifSubmitting = false;
            this.notifForm = this.emptyNotification();
            this.selectedUserIds = [];
            this.previewResult = null;
            this.loadNotifications();
            this.showSuccess(`Enviada a ${sendRes.push_sent} dispositivo(s). Omitidos: ${sendRes.skipped}.`);
          },
          error: (err) => {
            this.notifSubmitting = false;
            this.showError(err.error?.message || 'Error al enviar.');
          }
        });
      },
      error: (err) => {
        this.notifSubmitting = false;
        if (err.error?.errors) this.notifErrors = err.error.errors;
        this.showError(err.error?.message || 'Error al crear la notificación.');
      }
    });
  }

  recipientStatusBadge(status: string): string {
    return { available: 'badge-success', access_not_installed: 'badge-warning', no_access: 'badge-slate' }[status] ?? 'badge-slate';
  }

  recipientStatusLabel(status: string): string {
    return {
      available: 'Disponible',
      access_not_installed: 'Con acceso, sin instalar',
      no_access: 'Sin acceso a la app',
    }[status] ?? status;
  }

  targetLabel(target: string): string {
    return { all: 'Toda la empresa', area: 'Área', office: 'Oficina', user: 'Usuario', users: 'Grupo' }[target] ?? target;
  }

  deleteNotification(notification: any): void {
    if (!confirm(`¿Eliminar la notificación "${notification.title}"?`)) return;
    this.notificationsService.remove(notification.id).subscribe({
      next: () => {
        this.loadNotifications();
        this.showSuccess('Notificación eliminada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  private emptyForm(): any {
    return {
      first_name: '',
      last_name: '',
      employee_code: '',
      office_id: '',
      area_id: '',
      shift_id: '',
      is_area_manager: false,
      is_active: true,
      pin: '',
    };
  }

  private emptyNotification(): any {
    return {
      title: '',
      message: '',
      priority: 'normal',
      target_type: 'all',
      area_id: '',
      office_id: '',
      target_user_id: '',
    };
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
