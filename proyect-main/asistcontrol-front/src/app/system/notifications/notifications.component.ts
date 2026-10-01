import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { NotificationsService } from '../../services/notifications.service';
import { ExportButtonComponent } from '../../shared/export-button/export-button.component';
import { AppNotification } from '../../models/api.models';

@Component({
  selector: 'app-notifications',
  standalone: true,
  imports: [CommonModule, FormsModule, ExportButtonComponent],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-text-title">Notificaciones</h1>
          <p class="text-sm text-text-body mt-0.5">Envía avisos a todo el personal, por área o a un usuario</p>
        </div>
        <div class="flex items-center gap-2">
          <app-export-button resource="notifications"></app-export-button>
          <button (click)="openCreate()" class="btn-primary"><i class="bi bi-plus-lg"></i> Nueva notificación</button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div *ngFor="let n of notifications" class="dash-card p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="badge" [ngClass]="priorityBadge(n.priority)">{{ n.priority }}</span>
                <span class="text-[10px] text-text-body">{{ targetLabel(n.target_type) }}</span>
              </div>
              <h3 class="text-sm font-bold text-text-title mt-2">{{ n.title }}</h3>
              <p class="text-xs text-text-body mt-1">{{ n.message }}</p>
            </div>
            <div class="flex flex-col items-end gap-2">
              <span class="badge" [ngClass]="n.sent_at ? 'badge-success' : 'badge-slate'">{{ n.sent_at ? 'Enviada' : 'Borrador' }}</span>
              <div class="flex gap-2">
                <button *ngIf="!n.sent_at" (click)="send(n)" class="text-primary-medium text-xs" title="Enviar"><i class="bi bi-send"></i></button>
                <button (click)="remove(n)" class="text-red-500 text-xs" title="Eliminar"><i class="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
          <p *ngIf="n.reads_count !== undefined" class="text-[10px] text-text-body mt-3">{{ n.reads_count }} lectura(s)</p>
        </div>
        <div *ngIf="!loading && notifications.length === 0" class="dash-card p-10 text-center text-text-body lg:col-span-2">
          <i class="bi bi-bell text-2xl"></i>
          <p class="mt-2 text-xs">No hay notificaciones.</p>
        </div>
      </div>
    </div>

    <div *ngIf="showForm" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-neutral-900/60 modal-backdrop" (click)="cancelForm()"></div>
      <div class="flex min-h-full items-center justify-center p-4" (click)="cancelForm()">
        <div class="relative w-full max-w-lg bg-surface rounded-2xl shadow-2xl modal-card border border-black/10 dark:border-white/10" (click)="$event.stopPropagation()">
          <div class="flex items-center justify-between px-6 py-4 border-b border-black/10 dark:border-white/10">
            <h2 class="text-base font-bold text-text-title">Nueva notificación</h2>
            <button (click)="cancelForm()" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-text-body hover:bg-black/5 dark:hover:bg-white/10"><i class="bi bi-x-lg text-sm"></i></button>
          </div>
          <form (ngSubmit)="submitForm()" class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="sm:col-span-2">
                <label class="form-label">Título *</label>
                <input type="text" [(ngModel)]="form.title" name="title" required class="form-input" [ngClass]="{ 'border-red-400': errors.title }">
                <p *ngIf="errors.title" class="form-error">{{ errors.title[0] }}</p>
              </div>
              <div class="sm:col-span-2">
                <label class="form-label">Mensaje *</label>
                <textarea [(ngModel)]="form.message" name="message" rows="3" required class="form-input" [ngClass]="{ 'border-red-400': errors.message }"></textarea>
                <p *ngIf="errors.message" class="form-error">{{ errors.message[0] }}</p>
              </div>
              <div>
                <label class="form-label">Destino</label>
                <select [(ngModel)]="form.target_type" name="target_type" class="form-input">
                  <option value="all">Todos</option>
                  <option value="area">Por área</option>
                </select>
              </div>
              <div>
                <label class="form-label">Prioridad</label>
                <select [(ngModel)]="form.priority" name="priority" class="form-input">
                  <option value="normal">Normal</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
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
export class NotificationsComponent implements OnInit {

  notifications: AppNotification[] = [];
  loading = true;
  showForm = false;
  isSubmitting = false;
  errors: any = {};
  form: any = this.emptyForm();

  constructor(private notificationsService: NotificationsService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.notificationsService.list().subscribe({
      next: (res) => {
        this.notifications = res.notifications ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar las notificaciones.');
      }
    });
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
    this.notificationsService.create(this.form).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showForm = false;
        this.load();
        this.showSuccess(res.message || 'Notificación creada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al crear.');
      }
    });
  }

  send(notification: AppNotification): void {
    this.notificationsService.send(notification.id).subscribe({
      next: (res) => {
        this.load();
        this.showSuccess(res.message || 'Notificación enviada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al enviar.')
    });
  }

  remove(notification: AppNotification): void {
    if (!confirm(`¿Eliminar la notificación "${notification.title}"?`)) return;
    this.notificationsService.remove(notification.id).subscribe({
      next: () => {
        this.load();
        this.showSuccess('Notificación eliminada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  priorityBadge(priority: string): string {
    return { normal: 'badge-slate', high: 'badge-warning', urgent: 'badge-danger' }[priority] ?? 'badge-slate';
  }

  targetLabel(target: string): string {
    return { all: 'Todos', area: 'Por área', user: 'Usuario' }[target] ?? target;
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showForm) this.cancelForm();
  }

  private emptyForm(): any {
    return { title: '', message: '', target_type: 'all', priority: 'normal', is_active: true };
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
