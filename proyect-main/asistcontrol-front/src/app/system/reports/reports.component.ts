import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import * as Highcharts from 'highcharts';

import { ReportsService } from '../../services/reports.service';
import { PayrollService } from '../../services/payroll.service';
import { CatalogService } from '../../services/catalog.service';
import { EmployeesService } from '../../services/employees.service';
import { ExportButtonComponent } from '../../shared/export-button/export-button.component';
import { Area, Employee, Office, PayrollPeriod } from '../../models/api.models';

@Component({
  selector: 'app-reports',
  standalone: true,
  imports: [CommonModule, FormsModule, ExportButtonComponent],
  templateUrl: './reports.component.html',
  styleUrl: './reports.component.css'
})
export class ReportsComponent implements OnInit {

  @ViewChild('chartEl') chartEl!: ElementRef<HTMLDivElement>;

  tab: 'attendance' | 'payroll' = 'attendance';

  from = this.firstOfMonth();
  to = this.today();
  preset = 'month';
  officeId: any = '';
  areaId: any = '';
  employeeId: any = '';
  groupBy: 'company' | 'area' | 'office' | 'employee' = 'company';

  offices: Office[] = [];
  areas: Area[] = [];
  employees: Employee[] = [];

  attendanceData: any = null;
  loadingAttendance = false;

  periods: PayrollPeriod[] = [];
  periodId: number | null = null;
  payrollGroupBy: 'company' | 'area' | 'office' | 'employee' = 'company';
  payrollData: any = null;

  employeeModal: any = null;

  private chart: Highcharts.Chart | null = null;

  constructor(
    private reportsService: ReportsService,
    private payrollService: PayrollService,
    private catalogService: CatalogService,
    private employeesService: EmployeesService
  ) {}

  ngOnInit(): void {
    this.catalogService.offices().subscribe({ next: (res) => (this.offices = res.offices ?? []) });
    this.catalogService.areas().subscribe({ next: (res) => (this.areas = res.areas ?? []) });
    this.employeesService.list().subscribe({ next: (res) => (this.employees = res.employees ?? []) });
    this.payrollService.periods().subscribe({
      next: (res) => {
        this.periods = res.periods ?? [];
        if (this.periods.length > 0) this.periodId = this.periods[0].id;
      }
    });
    this.loadAttendance();
  }

  get exportFilters(): Record<string, any> {
    return {
      from: this.from, to: this.to, group_by: this.groupBy,
      office_id: this.officeId, area_id: this.areaId, employee_id: this.employeeId,
    };
  }

  setPreset(preset: string): void {
    this.preset = preset;
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth();
    if (preset === 'today') {
      this.from = this.to = this.iso(now);
    } else if (preset === 'yesterday') {
      const d = new Date(now); d.setDate(d.getDate() - 1);
      this.from = this.to = this.iso(d);
    } else if (preset === 'week') {
      const d = new Date(now); const day = (d.getDay() + 6) % 7; d.setDate(d.getDate() - day);
      this.from = this.iso(d); this.to = this.iso(now);
    } else if (preset === 'month') {
      this.from = `${y}-${String(m + 1).padStart(2, '0')}-01`; this.to = this.iso(now);
    } else if (preset === 'lastmonth') {
      const first = new Date(y, m - 1, 1); const last = new Date(y, m, 0);
      this.from = this.iso(first); this.to = this.iso(last);
    } else if (preset === 'year') {
      this.from = `${y}-01-01`; this.to = this.iso(now);
    }
    this.loadAttendance();
  }

  loadAttendance(): void {
    this.loadingAttendance = true;
    this.reportsService.attendance(this.exportFilters).subscribe({
      next: (res) => {
        this.attendanceData = res;
        this.loadingAttendance = false;
        setTimeout(() => this.renderChart());
      },
      error: () => {
        this.loadingAttendance = false;
        this.attendanceData = null;
      }
    });
  }

  loadPayroll(): void {
    if (!this.periodId) return;
    this.reportsService.payroll(this.periodId, this.payrollGroupBy).subscribe({
      next: (res) => (this.payrollData = res),
      error: () => (this.payrollData = null)
    });
  }

  openEmployee(group: any): void {
    if (this.groupBy !== 'employee' || !group.employee_id) return;
    this.reportsService.employeeReport(group.employee_id, this.from, this.to).subscribe({
      next: (res) => (this.employeeModal = res),
      error: () => {}
    });
  }

  closeEmployee(): void {
    this.employeeModal = null;
  }

  money(value: number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
  }

  timeOf(record: any, type: string): string {
    const found = (record.records || []).find((r: any) => r.type === type);
    return found ? found.recorded_at.slice(11, 16) : '—';
  }

  private renderChart(): void {
    if (!this.chartEl?.nativeElement || !this.attendanceData?.groups) return;
    if (this.chart) { this.chart.destroy(); this.chart = null; }

    const groups = this.attendanceData.groups ?? [];
    this.chart = Highcharts.chart(this.chartEl.nativeElement, {
      chart: { type: 'column', backgroundColor: 'transparent', height: 320 },
      title: { text: '' },
      xAxis: { categories: groups.map((g: any) => g.label), crosshair: true },
      yAxis: { min: 0, title: { text: 'Registros' } },
      legend: { enabled: true },
      credits: { enabled: false },
      series: [
        { name: 'Presentes', data: groups.map((g: any) => g.present), color: '#16a34a' },
        { name: 'Retardos', data: groups.map((g: any) => g.late), color: '#d97706' },
        { name: 'Faltas', data: groups.map((g: any) => g.absent), color: '#dc2626' },
      ],
    } as any);
  }

  private iso(d: Date): string {
    return d.toISOString().slice(0, 10);
  }

  private today(): string {
    return this.iso(new Date());
  }

  private firstOfMonth(): string {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
  }
}
