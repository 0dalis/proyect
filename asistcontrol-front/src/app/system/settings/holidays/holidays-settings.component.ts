import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { HolidaysService } from '../../../services/holidays.service';

@Component({
  selector: 'app-holidays-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in max-w-3xl">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-text-title">Días festivos</h1>
          <p class="text-sm text-text-body mt-0.5">Calendario laboral que afecta faltas y nómina</p>
        </div>
        <button (click)="openCreate()" class="btn-primary"><i class="bi bi-plus-lg"></i> Agregar</button>
      </div>

      <div class="dash-card overflow-hidden">
        <table class="table-theme">
          <thead><tr><th>Nombre</th><th>Fecha</th><th>Con goce</th><th class="text-right">Acciones</th></tr></thead>
          <tbody>
            <tr *ngFor="let h of holidays">
              <td class="font-semibold text-text-title">{{ h.name }}</td>
              <td>{{ h.date | date: 'dd/MM/yyyy' }}</td>
              <td><span class="badge" [ngClass]="h.is_paid ? 'badge-success' : 'badge-slate'">{{ h.is_paid ? 'Sí' : 'No' }}</span></td>
              <td class="text-right"><button (click)="remove(h)" class="text-red-500 hover:text-red-700 text-xs"><i class="bi bi-trash"></i></button></td>
            </tr>
            <tr *ngIf="!loading && holidays.length === 0"><td colspan="4" class="py-10 text-center text-text-body">No hay días festivos configurados.</td></tr>
            <tr *ngIf="loading"><td colspan="4" class="py-10 text-center text-text-body">Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div *ngIf="showForm" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-neutral-900/60 modal-backdrop" (click)="showForm = false"></div>
      <div class="flex min-h-full items-center justify-center p-4" (click)="showForm = false">
        <div class="relative w-full max-w-md bg-surface rounded-2xl shadow-2xl modal-card border border-black/10 dark:border-white/10" (click)="$event.stopPropagation()">
          <div class="px-6 py-4 border-b border-black/10 dark:border-white/10">
            <h2 class="text-base font-bold text-text-title">Nuevo día festivo</h2>
          </div>
          <form (ngSubmit)="submitForm()" class="p-6">
            <label class="form-label">Nombre *</label>
            <input type="text" [(ngModel)]="form.name" name="name" required class="form-input" [ngClass]="{ 'border-red-400': errors.name }">
            <p *ngIf="errors.name" class="form-error">{{ errors.name[0] }}</p>
            <label class="form-label mt-4">Fecha *</label>
            <input type="date" [(ngModel)]="form.date" name="date" required class="form-input" [ngClass]="{ 'border-red-400': errors.date }">
            <p *ngIf="errors.date" class="form-error">{{ errors.date[0] }}</p>
            <label class="flex items-center gap-2 text-xs text-text-body mt-4">
              <input type="checkbox" [(ngModel)]="form.is_paid" name="is_paid" class="w-4 h-4 rounded"> Con goce de sueldo
            </label>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-black/10 dark:border-white/10">
              <button type="button" (click)="showForm = false" class="btn-secondary">Cancelar</button>
              <button type="submit" [disabled]="isSubmitting" class="btn-primary">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `,
  styles: [':host { display: block; }']
})
export class HolidaysSettingsComponent implements OnInit {

  holidays: any[] = [];
  loading = true;
  showForm = false;
  isSubmitting = false;
  errors: any = {};
  form: any = { name: '', date: '', is_paid: true };

  constructor(private holidaysService: HolidaysService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.holidaysService.list().subscribe({
      next: (res) => {
        this.holidays = res.holidays ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar los días festivos.');
      }
    });
  }

  openCreate(): void {
    this.form = { name: '', date: '', is_paid: true };
    this.errors = {};
    this.showForm = true;
  }

  submitForm(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};
    this.holidaysService.create(this.form).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showForm = false;
        this.load();
        this.showSuccess(res.message || 'Día festivo guardado.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  remove(holiday: any): void {
    if (!confirm(`¿Eliminar "${holiday.name}"?`)) return;
    this.holidaysService.remove(holiday.id).subscribe({
      next: () => {
        this.load();
        this.showSuccess('Día festivo eliminado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showForm) this.showForm = false;
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
