import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { AuditService } from '../../../services/audit.service';

@Component({
  selector: 'app-audit',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="flex flex-col gap-6 animate-fade-in">
      <div>
        <h1 class="text-2xl font-bold text-text-title">Auditoría</h1>
        <p class="text-sm text-text-body mt-0.5">Registro de acciones realizadas en el sistema</p>
      </div>

      <div class="dash-card p-4">
        <div class="flex flex-col sm:flex-row gap-3">
          <input type="text" [(ngModel)]="action" (keyup.enter)="load()" placeholder="Filtrar por acción (ej: user.created)" class="form-input flex-1">
          <button (click)="load()" class="btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
      </div>

      <div class="dash-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table-theme">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>Detalle</th></tr></thead>
            <tbody>
              <tr *ngFor="let l of logs">
                <td class="whitespace-nowrap">{{ l.created_at | date: 'dd/MM/yyyy HH:mm' }}</td>
                <td>{{ l.user?.email || '—' }}</td>
                <td><span class="badge badge-chip">{{ l.action }}</span></td>
                <td class="font-mono text-[10px]">{{ l.entity_type ? l.entity_type.split('\\\\').pop() + ' #' + l.entity_id : '—' }}</td>
                <td class="text-[10px] text-text-body">{{ l.meta | json }}</td>
              </tr>
              <tr *ngIf="!loading && logs.length === 0"><td colspan="5" class="py-10 text-center text-text-body">Sin registros.</td></tr>
              <tr *ngIf="loading"><td colspan="5" class="py-10 text-center text-text-body">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="flex items-center justify-between px-4 py-3 border-t border-black/10 dark:border-white/10 text-xs text-text-body">
          <span>{{ total }} registro(s)</span>
          <div class="flex items-center gap-2">
            <button (click)="changePage(-1)" [disabled]="page <= 1" class="btn-secondary disabled:opacity-40"><i class="bi bi-chevron-left"></i></button>
            <span>Página {{ page }} de {{ lastPage }}</span>
            <button (click)="changePage(1)" [disabled]="page >= lastPage" class="btn-secondary disabled:opacity-40"><i class="bi bi-chevron-right"></i></button>
          </div>
        </div>
      </div>
    </div>
  `,
  styles: [':host { display: block; }']
})
export class AuditComponent implements OnInit {

  logs: any[] = [];
  loading = true;
  action = '';
  page = 1;
  lastPage = 1;
  total = 0;

  constructor(private auditService: AuditService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.auditService.list({ action: this.action, page: this.page }).subscribe({
      next: (res) => {
        this.logs = res.data ?? [];
        this.total = res.total ?? 0;
        this.lastPage = res.last_page ?? 1;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  changePage(delta: number): void {
    const next = this.page + delta;
    if (next < 1 || next > this.lastPage) return;
    this.page = next;
    this.load();
  }
}
