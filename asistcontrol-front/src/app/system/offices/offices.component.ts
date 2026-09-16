import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { CatalogService } from '../../services/catalog.service';
import { ExportButtonComponent } from '../../shared/export-button/export-button.component';
import { Office } from '../../models/api.models';

@Component({
  selector: 'app-offices',
  standalone: true,
  imports: [CommonModule, FormsModule, ExportButtonComponent],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-text-title">Oficinas</h1>
          <p class="text-sm text-text-body mt-0.5">Ubicaciones físicas y geocercas de asistencia</p>
        </div>
        <div class="flex items-center gap-2">
          <app-export-button resource="offices"></app-export-button>
          <button (click)="openCreate()" class="btn-primary"><i class="bi bi-plus-lg"></i> Nueva oficina</button>
        </div>
      </div>

      <div class="dash-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table-theme">
            <thead>
              <tr><th>Nombre</th><th>Código</th><th>Coordenadas</th><th>Radio</th><th>Zona horaria</th><th>Turnos</th><th>Estado</th><th class="text-right">Acciones</th></tr>
            </thead>
            <tbody>
              <tr *ngFor="let o of offices">
                <td class="font-semibold text-text-title">{{ o.name }}</td>
                <td>{{ o.code || '—' }}</td>
                <td class="font-mono text-[10px]">{{ o.latitude }}, {{ o.longitude }}</td>
                <td>{{ o.radius_meters }} m</td>
                <td>{{ o.timezone }}</td>
                <td>{{ o.shifts?.length || 0 }}</td>
                <td><span class="badge" [ngClass]="o.is_active ? 'badge-success' : 'badge-slate'">{{ o.is_active ? 'Activa' : 'Inactiva' }}</span></td>
                <td class="text-right whitespace-nowrap">
                  <button (click)="openEdit(o)" class="text-primary-medium hover:text-primary-dark text-xs mr-2"><i class="bi bi-pencil"></i></button>
                  <button (click)="remove(o)" class="text-red-500 hover:text-red-700 text-xs"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
              <tr *ngIf="!loading && offices.length === 0">
                <td colspan="8" class="py-10 text-center text-text-body"><i class="bi bi-geo-alt text-2xl"></i><p class="mt-2 text-xs">No hay oficinas registradas.</p></td>
              </tr>
              <tr *ngIf="loading"><td colspan="8" class="py-10 text-center text-text-body">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div *ngIf="showForm" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-neutral-900/60 modal-backdrop" (click)="cancelForm()"></div>
      <div class="flex min-h-full items-center justify-center p-4" (click)="cancelForm()">
        <div class="relative w-full max-w-xl bg-surface rounded-2xl shadow-2xl modal-card border border-black/10 dark:border-white/10" (click)="$event.stopPropagation()">
          <div class="flex items-center justify-between px-6 py-4 border-b border-black/10 dark:border-white/10">
            <h2 class="text-base font-bold text-text-title">{{ editingId ? 'Editar oficina' : 'Nueva oficina' }}</h2>
            <button (click)="cancelForm()" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-text-body hover:bg-black/5 dark:hover:bg-white/10"><i class="bi bi-x-lg text-sm"></i></button>
          </div>
          <form (ngSubmit)="submitForm()" class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Nombre *</label>
                <input type="text" [(ngModel)]="form.name" name="name" required class="form-input" [ngClass]="{ 'border-red-400': errors.name }">
                <p *ngIf="errors.name" class="form-error">{{ errors.name[0] }}</p>
              </div>
              <div>
                <label class="form-label">Código</label>
                <input type="text" [(ngModel)]="form.code" name="code" class="form-input">
              </div>
              <div>
                <label class="form-label">Latitud *</label>
                <input type="number" step="0.000001" [(ngModel)]="form.latitude" name="latitude" required class="form-input" [ngClass]="{ 'border-red-400': errors.latitude }">
                <p *ngIf="errors.latitude" class="form-error">{{ errors.latitude[0] }}</p>
              </div>
              <div>
                <label class="form-label">Longitud *</label>
                <input type="number" step="0.000001" [(ngModel)]="form.longitude" name="longitude" required class="form-input" [ngClass]="{ 'border-red-400': errors.longitude }">
                <p *ngIf="errors.longitude" class="form-error">{{ errors.longitude[0] }}</p>
              </div>
              <div>
                <label class="form-label">Radio (m)</label>
                <input type="number" min="10" max="5000" [(ngModel)]="form.radius_meters" name="radius_meters" class="form-input">
              </div>
              <div>
                <label class="form-label">Zona horaria</label>
                <input type="text" [(ngModel)]="form.timezone" name="timezone" class="form-input">
              </div>
              <div>
                <label class="form-label">País</label>
                <input type="text" [(ngModel)]="form.country" name="country" class="form-input">
              </div>
              <div class="flex items-center gap-2 pt-6">
                <input type="checkbox" [(ngModel)]="form.is_active" name="is_active" class="w-4 h-4 rounded">
                <span class="text-xs text-text-body">Activa</span>
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
  `,
  styles: [':host { display: block; }']
})
export class OfficesComponent implements OnInit {

  offices: Office[] = [];
  loading = true;
  showForm = false;
  editingId: number | null = null;
  isSubmitting = false;
  errors: any = {};
  form: any = this.emptyForm();

  constructor(private catalogService: CatalogService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.catalogService.offices().subscribe({
      next: (res) => {
        this.offices = res.offices ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar las oficinas.');
      }
    });
  }

  openCreate(): void {
    this.editingId = null;
    this.form = this.emptyForm();
    this.errors = {};
    this.showForm = true;
  }

  openEdit(office: Office): void {
    this.editingId = office.id;
    this.form = {
      name: office.name,
      code: office.code || '',
      latitude: office.latitude,
      longitude: office.longitude,
      radius_meters: office.radius_meters,
      timezone: office.timezone || 'America/Mexico_City',
      country: office.country || '',
      is_active: office.is_active,
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
    const request = this.editingId
      ? this.catalogService.updateOffice(this.editingId, this.form)
      : this.catalogService.createOffice(this.form);
    request.subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showForm = false;
        this.load();
        this.showSuccess(res.message || 'Oficina guardada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  remove(office: Office): void {
    if (!confirm(`¿Eliminar la oficina "${office.name}"?`)) return;
    this.catalogService.deleteOffice(office.id).subscribe({
      next: () => {
        this.load();
        this.showSuccess('Oficina eliminada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showForm) this.cancelForm();
  }

  private emptyForm(): any {
    return {
      name: '', code: '', latitude: 19.4326, longitude: -99.1332,
      radius_meters: 100, timezone: 'America/Mexico_City', country: 'Mexico', is_active: true,
    };
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
