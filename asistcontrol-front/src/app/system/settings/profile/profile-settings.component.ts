import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import Toastify from 'toastify-js';

import { ProfileService } from '../../../services/profile.service';

@Component({
  selector: 'app-profile-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in max-w-3xl">
      <div>
        <h1 class="text-2xl font-bold text-text-title">Perfil y seguridad</h1>
        <p class="text-sm text-text-body mt-0.5">Administra tus datos de acceso</p>
      </div>

      <div class="dash-card p-6">
        <h2 class="text-sm font-semibold text-text-title mb-4">Datos de la cuenta</h2>
        <form (ngSubmit)="saveEmail()">
          <label class="form-label">Correo electrónico</label>
          <input type="email" [(ngModel)]="email" name="email" required class="form-input" [ngClass]="{ 'border-red-400': errors.email }">
          <p *ngIf="errors.email" class="form-error">{{ errors.email[0] }}</p>
          <div class="flex justify-end mt-4">
            <button type="submit" [disabled]="isSubmitting" class="btn-primary">{{ isSubmitting ? 'Guardando...' : 'Guardar correo' }}</button>
          </div>
        </form>
      </div>

      <div class="dash-card p-6">
        <h2 class="text-sm font-semibold text-text-title mb-4">Cambiar contraseña</h2>
        <form (ngSubmit)="savePassword()">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="form-label">Contraseña actual *</label>
              <input type="password" [(ngModel)]="passwordForm.current_password" name="current_password" required class="form-input" [ngClass]="{ 'border-red-400': errors.current_password }">
              <p *ngIf="errors.current_password" class="form-error">{{ errors.current_password[0] }}</p>
            </div>
            <div>
              <label class="form-label">Nueva contraseña *</label>
              <input type="password" [(ngModel)]="passwordForm.password" name="password" required minlength="8" class="form-input" [ngClass]="{ 'border-red-400': errors.password }">
              <p *ngIf="errors.password" class="form-error">{{ errors.password[0] }}</p>
            </div>
            <div>
              <label class="form-label">Confirmar contraseña *</label>
              <input type="password" [(ngModel)]="passwordForm.password_confirmation" name="password_confirmation" required class="form-input">
            </div>
          </div>
          <div class="flex justify-end mt-4">
            <button type="submit" [disabled]="isSubmitting" class="btn-primary">{{ isSubmitting ? 'Guardando...' : 'Cambiar contraseña' }}</button>
          </div>
        </form>
      </div>
    </div>
  `,
  styles: [':host { display: block; }']
})
export class ProfileSettingsComponent implements OnInit {

  email = '';
  passwordForm = { current_password: '', password: '', password_confirmation: '' };
  isSubmitting = false;
  errors: any = {};

  constructor(private profileService: ProfileService) {}

  ngOnInit(): void {
    this.profileService.show().subscribe({
      next: (res) => (this.email = res.user?.email ?? ''),
      error: () => this.showError('No se pudo cargar el perfil.')
    });
  }

  saveEmail(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};
    this.profileService.update({ email: this.email }).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showSuccess(res.message || 'Perfil actualizado.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  savePassword(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};
    this.profileService.updatePassword(this.passwordForm).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.passwordForm = { current_password: '', password: '', password_confirmation: '' };
        this.showSuccess(res.message || 'Contraseña actualizada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
