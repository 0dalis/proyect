import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

import Toastify from 'toastify-js';

import { PayrollService } from '../../services/payroll.service';
import { ExportButtonComponent } from '../../shared/export-button/export-button.component';
import { PayrollConcept, PayrollPeriod, PayrollSettings } from '../../models/api.models';

@Component({
  selector: 'app-payroll',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, ExportButtonComponent],
  templateUrl: './payroll.component.html',
  styleUrl: './payroll.component.css'
})
export class PayrollComponent implements OnInit {

  tab: 'periods' | 'concepts' | 'settings' = 'periods';

  periods: PayrollPeriod[] = [];
  concepts: PayrollConcept[] = [];
  settings: PayrollSettings = this.emptySettings();
  loading = true;

  showPeriodForm = false;
  isSubmitting = false;
  errors: any = {};
  periodForm: any = this.emptyPeriod();

  showConceptForm = false;
  editingConceptId: number | null = null;
  conceptForm: any = this.emptyConcept();

  constructor(private payrollService: PayrollService) {}

  ngOnInit(): void {
    this.loadPeriods();
    this.loadConcepts();
    this.loadSettings();
  }

  loadPeriods(): void {
    this.loading = true;
    this.payrollService.periods().subscribe({
      next: (res) => {
        this.periods = res.periods ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudieron cargar los periodos.');
      }
    });
  }

  loadConcepts(): void {
    this.payrollService.concepts().subscribe({ next: (res) => (this.concepts = res.concepts ?? []) });
  }

  loadSettings(): void {
    this.payrollService.getSettings().subscribe({ next: (res) => (this.settings = res.settings) });
  }

  // ----- Periodos -----
  openPeriodForm(): void {
    this.periodForm = this.emptyPeriod();
    this.errors = {};
    this.showPeriodForm = true;
  }

  cancelPeriodForm(): void {
    this.showPeriodForm = false;
    this.errors = {};
  }

  submitPeriod(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};

    this.payrollService.createPeriod(this.periodForm).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showPeriodForm = false;
        this.loadPeriods();
        this.showSuccess(res.message || 'Periodo creado.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al crear el periodo.');
      }
    });
  }

  calculate(period: PayrollPeriod): void {
    this.payrollService.calculatePeriod(period.id).subscribe({
      next: (res) => {
        this.loadPeriods();
        this.showSuccess(res.message || 'Nómina calculada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al calcular.')
    });
  }

  close(period: PayrollPeriod): void {
    if (!confirm(`¿Cerrar y bloquear el periodo "${period.name}"?`)) return;
    this.payrollService.closePeriod(period.id).subscribe({
      next: (res) => {
        this.loadPeriods();
        this.showSuccess(res.message || 'Periodo cerrado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al cerrar.')
    });
  }

  removePeriod(period: PayrollPeriod): void {
    if (!confirm(`¿Eliminar el periodo "${period.name}"?`)) return;
    this.payrollService.deletePeriod(period.id).subscribe({
      next: () => {
        this.loadPeriods();
        this.showSuccess('Periodo eliminado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  statusBadge(status: string): string {
    return { draft: 'badge-slate', calculated: 'badge-warning', closed: 'badge-success' }[status] ?? 'badge-slate';
  }

  statusLabel(status: string): string {
    return { draft: 'Borrador', calculated: 'Calculado', closed: 'Cerrado' }[status] ?? status;
  }

  frequencyLabel(f: string): string {
    return { weekly: 'Semanal', biweekly: 'Catorcenal', monthly: 'Mensual' }[f] ?? f;
  }

  // ----- Conceptos -----
  openConceptForm(concept?: PayrollConcept): void {
    this.editingConceptId = concept?.id ?? null;
    this.conceptForm = concept ? { ...concept } : this.emptyConcept();
    this.errors = {};
    this.showConceptForm = true;
  }

  cancelConceptForm(): void {
    this.showConceptForm = false;
    this.errors = {};
  }

  submitConcept(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};

    const request = this.editingConceptId
      ? this.payrollService.updateConcept(this.editingConceptId, this.conceptForm)
      : this.payrollService.createConcept(this.conceptForm);

    request.subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.showConceptForm = false;
        this.loadConcepts();
        this.showSuccess(res.message || 'Concepto guardado.');
      },
      error: (err) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  removeConcept(concept: PayrollConcept): void {
    if (!confirm(`¿Eliminar el concepto "${concept.name}"?`)) return;
    this.payrollService.deleteConcept(concept.id).subscribe({
      next: () => {
        this.loadConcepts();
        this.showSuccess('Concepto eliminado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al eliminar.')
    });
  }

  // ----- Configuración -----
  saveSettings(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.payrollService.updateSettings(this.settings).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.settings = res.settings;
        this.showSuccess(res.message || 'Configuración guardada.');
      },
      error: (err) => {
        this.isSubmitting = false;
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  @HostListener('window:keydown.escape')
  onEscape(): void {
    if (this.showPeriodForm) this.cancelPeriodForm();
    if (this.showConceptForm) this.cancelConceptForm();
  }

  private emptyPeriod(): any {
    return { name: '', frequency: 'monthly', start_date: '', end_date: '', notes: '' };
  }

  private emptyConcept(): any {
    return { name: '', type: 'bonus', calculation: 'fixed', amount: 0, is_recurring: true, is_active: true, description: '' };
  }

  private emptySettings(): PayrollSettings {
    return {
      currency: 'MXN',
      timezone: 'America/Mexico_City',
      default_pay_frequency: 'monthly',
      attendance_bonus_enabled: false,
      attendance_bonus_amount: 0,
      late_penalty_amount: 0,
      absence_penalty_amount: 0,
      overtime_enabled: true,
    };
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
