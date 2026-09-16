import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { CatalogService } from '../../services/catalog.service';
import { ExportButtonComponent } from '../../shared/export-button/export-button.component';
import { Area } from '../../models/api.models';

@Component({
  selector: 'app-areas',
  standalone: true,
  imports: [CommonModule, FormsModule, ExportButtonComponent],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-text-title">Áreas</h1>
          <p class="text-sm text-text-body mt-0.5">Departamentos de tu empresa</p>
        </div>
        <div class="flex items-center gap-2">
          <app-export-button resource="areas"></app-export-button>
          <button (click)="openCreate()" class="btn-primary"><i class="bi bi-plus-lg"></i> Nueva área</button>
        </div>
      </div>

      <div class="dash-card overflow-hidden">
        <table class="table-theme">
          <thead>
            <tr><th>Nombre</th><th>Estado</th><th class="text-right">Acciones</th></tr>
          </thead>
          <tbody>
            <tr *ngFor="let a of areas">
              <td class="font-semibold text-text-title">{{ a.name }}</td>
              <td><span class="badge" [ngClass]="a.is_active ? 'badge-success' : 'badge-slate'">{{ a.is_active ? 'Activa' : 'Inactiva' }}</span></td>
              <td class="text-right whitespace-nowrap">
                <button (click)="openEdit(a)" class="text-primary-medium hover:text-primary-dark text-xs mr-2"><i class="bi bi-pencil"></i></button>
                <button (click)="remove(a)" class="text-red-500 hover:text-red-700 text-xs"><i class="bi bi-trash"></i></button>
              </td>
            </tr>
            <tr *ngIf="!loading && areas.length === 0">
              <td colspan="3" class="py-10 text-center text-text-body"><i class="bi bi-diagram-3 text-2xl"></i><p class="mt-2 text-xs">No hay áreas registradas.</p></td>
            </tr>
            <tr *ngIf="loading"><td colspan="3" class="py-10 text-center text-text-body">Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div *ngIf="showForm" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-neutral-900/60 modal-backdrop" (click)="cancelForm()"></div>
      <div class="flex min-h-full items-center justify-center p-4" (click)="cancelForm()">
        <div class="relative w-full max-w-md bg-surface rounded-2xl shadow-2xl modal-card border border-black/10 dark:border-white/10" (click)="$event.stopPropagation()">
          <div class="flex items-center justify-between px-6 py-4 border-b border-black/10 dark:border-white/10">
            <h2 class="text-base font-bold text-text-title">{{ editingId ? 'Editar área' : 'Nueva área' }}</h2>
            <button (click)="cancelForm()" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-text-body hover:bg-black/5 dark:hover:bg-white/10"><i class="bi bi-x-lg text-sm"></i></button>
          </div>
          <form (ngSubmit)="submitForm()" class="p-6">
            <label class="form-label">Nombre *</label>
            <input type="text" [(ngModel)]="form.name" name="name" required class="form-input" [ngClass]="{ 'border-red-400': errors.name }">
            <p *ngIf="errors.name" class="form-error">{{ errors.name[0] }}</p>
            <label class="flex items-center gap-2 text-xs text-text-body mt-4">
              <input type="checkbox" [(ngModel)]="form.is_active" name="is_active" class="w-4 h-4 rounded"> Activa
            </label>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-black/10 dark:border-white/10">
              <button type="button" (click)="cancelForm()" class="btn-secondary">Cancelar</button>
              <button type="submit" [disabled]="isSubmitting" class="btn-primary">{{ isSubmitting ? 'Guardando...' : 'Guardar' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `,
  styles: [':host { display: block; }']
})
export class AreasComponent implements OnInit {

  areas: Area[] = [];
  loading = true;
  showForm = false;
  editingId: number | null = null;
  isSubmitting = false;
  errors: any = {};
  form: any = { name: '', is_active: true };

  constructor(private catalogService: CatalogService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.catalogService.areas().subscribe({
      next: (res) => {
        this.areas = res.areas ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar las áreas.');
      }
    });
  }

  openCreate(): void {
    this.editingId = null;
    this.form = { name: '', is_active: true };
    this.errors = {};
    this.showForm = true;
  }

  openEdit(area: Area): void {
    this.editingId = area.id;
    this.form = { name: area.name, is_active: area.is_active };
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
    const request = this.editingId
      ? this.catalogService.updateArea(this.editingId, this.form)
      : this.catalogService.createArea(this.form);
    request.subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showForm = false;
        this.load();
        this.showSuccess(res.message || 'Área guardada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  remove(area: Area): void {
    if (!confirm(`¿Eliminar el área "${area.name}"?`)) return;
    this.catalogService.deleteArea(area.id).subscribe({
      next: () => {
        this.load();
        this.showSuccess('Área eliminada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showForm) this.cancelForm();
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
