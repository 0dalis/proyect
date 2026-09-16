import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { CompleteProfileService } from '../../../services/public/completeprofile.services';
import { PayrollService } from '../../../services/payroll.service';
import { PayrollSettings } from '../../../models/api.models';

@Component({
  selector: 'app-general-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in max-w-3xl">
      <div>
        <h1 class="text-2xl font-bold text-text-title">Configuración general</h1>
        <p class="text-sm text-text-body mt-0.5">Datos de la empresa y parámetros de nómina</p>
      </div>

      <div class="dash-card p-6">
        <h2 class="text-sm font-semibold text-text-title mb-4">Empresa</h2>
        <form (ngSubmit)="saveCompany()">
          <label class="form-label">Nombre de la empresa</label>
          <input type="text" [(ngModel)]="companyName" name="companyName" required class="form-input" [ngClass]="{ 'border-red-400': errors.name }">
          <p *ngIf="errors.name" class="form-error">{{ errors.name[0] }}</p>
          <div class="flex justify-end mt-4">
            <button type="submit" [disabled]="isSubmitting" class="btn-primary">{{ isSubmitting ? 'Guardando...' : 'Guardar' }}</button>
          </div>
        </form>
      </div>

      <div class="dash-card p-6">
        <h2 class="text-sm font-semibold text-text-title mb-4">Parámetros de nómina</h2>
        <form (ngSubmit)="savePayroll()">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Moneda</label>
              <input type="text" maxlength="3" [(ngModel)]="settings.currency" name="currency" class="form-input uppercase">
            </div>
            <div>
              <label class="form-label">Zona horaria</label>
              <input type="text" [(ngModel)]="settings.timezone" name="timezone" class="form-input">
            </div>
            <div>
              <label class="form-label">Frecuencia de pago por defecto</label>
              <select [(ngModel)]="settings.default_pay_frequency" name="default_pay_frequency" class="form-input">
                <option value="weekly">Semanal</option>
                <option value="biweekly">Catorcenal</option>
                <option value="monthly">Mensual</option>
              </select>
            </div>
            <div>
              <label class="form-label">Días de vacaciones por defecto</label>
              <input type="number" min="0" max="60" [(ngModel)]="settings.default_vacation_days" name="default_vacation_days" class="form-input">
            </div>
            <div class="flex items-center gap-2 pt-6">
              <input type="checkbox" [(ngModel)]="settings.overtime_enabled" name="overtime_enabled" class="w-4 h-4 rounded">
              <span class="text-xs text-text-body">Calcular horas extra</span>
            </div>
          </div>
          <div class="flex justify-end mt-4">
            <button type="submit" [disabled]="isSubmitting" class="btn-primary">{{ isSubmitting ? 'Guardando...' : 'Guardar parámetros' }}</button>
          </div>
        </form>
      </div>
    </div>
  `,
  styles: [':host { display: block; }']
})
export class GeneralSettingsComponent implements OnInit {

  companyName = '';
  settings: PayrollSettings = this.emptySettings();
  isSubmitting = false;
  errors: any = {};

  constructor(
    private completeProfileService: CompleteProfileService,
    private payrollService: PayrollService
  ) {}

  ngOnInit(): void {
    this.completeProfileService.getStatus().subscribe({
      next: (res) => (this.companyName = res.company?.name ?? ''),
      error: () => {}
    });
    this.payrollService.getSettings().subscribe({
      next: (res) => (this.settings = res.settings),
      error: () => {}
    });
  }

  saveCompany(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};
    this.completeProfileService.updateProfile({ name: this.companyName }).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showSuccess(res.message || 'Empresa actualizada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  savePayroll(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.payrollService.updateSettings(this.settings).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.settings = res.settings;
        this.showSuccess(res.message || 'Parámetros guardados.');
      },
      error: (err) => {
        this.isSubmitting = false;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  private emptySettings(): PayrollSettings {
    return {
      currency: 'MXN', timezone: 'America/Mexico_City', default_pay_frequency: 'monthly',
      default_vacation_days: 12,
      attendance_bonus_enabled: false, attendance_bonus_amount: 0,
      late_penalty_amount: 0, absence_penalty_amount: 0, overtime_enabled: true,
    };
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
