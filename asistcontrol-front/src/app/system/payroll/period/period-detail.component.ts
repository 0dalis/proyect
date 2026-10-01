import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';

import Toastify from 'toastify-js';

import { PayrollService } from '../../../services/payroll.service';
import { ExportService } from '../../../services/export.service';
import { ExportButtonComponent } from '../../../shared/export-button/export-button.component';
import { PayrollPeriod } from '../../../models/api.models';

@Component({
  selector: 'app-period-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, ExportButtonComponent],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in" *ngIf="period">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <a routerLink="/asistcontrol/payroll" class="btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
          <div>
            <h1 class="text-2xl font-bold text-text-title">{{ period.name }}</h1>
            <p class="text-sm text-text-body">
              {{ period.start_date | date: 'dd/MM/yyyy' }} – {{ period.end_date | date: 'dd/MM/yyyy' }}
              · <span class="badge" [ngClass]="statusBadge(period.status)">{{ statusLabel(period.status) }}</span>
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <app-export-button resource="payroll_items" [filters]="{ period_id: period.id }"></app-export-button>
          <app-export-button resource="bank_layout" [filters]="{ period_id: period.id }"></app-export-button>
          <button (click)="calculate()" [disabled]="period.status === 'closed'" class="btn-secondary disabled:opacity-40">
            <i class="bi bi-calculator"></i> Recalcular
          </button>
          <button (click)="close()" [disabled]="period.status !== 'calculated'" class="btn-primary disabled:opacity-40">
            <i class="bi bi-lock"></i> Cerrar periodo
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dash-card p-4">
          <p class="text-[10px] uppercase text-text-body">Percepciones</p>
          <p class="text-lg font-bold text-text-title">{{ totals.gross | currency: 'MXN':'symbol-narrow':'1.0-2' }}</p>
        </div>
        <div class="dash-card p-4">
          <p class="text-[10px] uppercase text-text-body">Deducciones</p>
          <p class="text-lg font-bold text-text-title">{{ totals.deductions | currency: 'MXN':'symbol-narrow':'1.0-2' }}</p>
        </div>
        <div class="dash-card p-4">
          <p class="text-[10px] uppercase text-text-body">Neto total</p>
          <p class="text-lg font-bold text-success-green">{{ totals.net | currency: 'MXN':'symbol-narrow':'1.0-2' }}</p>
        </div>
        <div class="dash-card p-4">
          <p class="text-[10px] uppercase text-text-body">Empleados</p>
          <p class="text-lg font-bold text-text-title">{{ period.items?.length || 0 }}</p>
        </div>
      </div>

      <div class="dash-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table-theme">
            <thead>
              <tr>
                <th>Empleado</th>
                <th>Días</th>
                <th>Retardos</th>
                <th>Faltas</th>
                <th>Extra</th>
                <th>Base</th>
                <th>Horas extra</th>
                <th>Bonos</th>
                <th>Deducciones</th>
                <th>Neto</th>
                <th>Recibo</th>
              </tr>
            </thead>
            <tbody>
              <tr *ngFor="let i of period.items">
                <td class="font-semibold text-text-title">{{ i.employee?.first_name }} {{ i.employee?.last_name }}</td>
                <td>{{ i.worked_days }}</td>
                <td>{{ i.late_count }}</td>
                <td>{{ i.absence_count }}</td>
                <td>{{ i.overtime_minutes }} min</td>
                <td>{{ i.base_amount | number: '1.2-2' }}</td>
                <td>{{ i.overtime_amount | number: '1.2-2' }}</td>
                <td class="text-success-green">{{ i.bonuses_amount | number: '1.2-2' }}</td>
                <td class="text-red-500">{{ i.deductions_amount | number: '1.2-2' }}</td>
                <td class="font-bold text-text-title">{{ i.net_amount | number: '1.2-2' }}</td>
                <td>
                  <button (click)="payslip(i)" class="text-primary-medium hover:text-primary-dark text-xs" title="Descargar recibo PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </button>
                </td>
              </tr>
              <tr *ngIf="!period.items || period.items.length === 0">
                <td colspan="11" class="py-10 text-center text-text-body">
                  <i class="bi bi-calculator text-2xl"></i>
                  <p class="mt-2 text-xs">Aún no se ha calculado la nómina de este periodo.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `,
  styles: [':host { display: block; }']
})
export class PeriodDetailComponent implements OnInit {

  period: PayrollPeriod | null = null;

  constructor(
    private route: ActivatedRoute,
    private payrollService: PayrollService,
    private exportService: ExportService
  ) {}

  ngOnInit(): void {
    this.load();
  }

  get totals(): { gross: number; deductions: number; net: number } {
    const items = this.period?.items ?? [];
    return {
      gross: items.reduce((s, i) => s + (i.gross_amount || 0), 0),
      deductions: items.reduce((s, i) => s + (i.deductions_amount || 0), 0),
      net: items.reduce((s, i) => s + (i.net_amount || 0), 0),
    };
  }

  load(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.payrollService.getPeriod(id).subscribe({
      next: (res) => (this.period = res.period),
      error: () => this.showError('No se pudo cargar el periodo.')
    });
  }

  calculate(): void {
    if (!this.period) return;
    this.payrollService.calculatePeriod(this.period.id).subscribe({
      next: (res) => {
        this.period = res.period;
        this.showSuccess(res.message || 'Nómina calculada.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al calcular.')
    });
  }

  close(): void {
    if (!this.period) return;
    if (!confirm('¿Cerrar y bloquear el periodo?')) return;
    this.payrollService.closePeriod(this.period.id).subscribe({
      next: (res) => {
        this.period = res.period;
        this.showSuccess(res.message || 'Periodo cerrado.');
      },
      error: (err) => this.showError(err.error?.message || 'Error al cerrar.')
    });
  }

  statusBadge(status: string): string {
    return { draft: 'badge-slate', calculated: 'badge-warning', closed: 'badge-success' }[status] ?? 'badge-slate';
  }

  statusLabel(status: string): string {
    return { draft: 'Borrador', calculated: 'Calculado', closed: 'Cerrado' }[status] ?? status;
  }

  payslip(item: any): void {
    this.exportService.downloadPayslip(item.id, item.employee?.employee_code ?? '');
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
