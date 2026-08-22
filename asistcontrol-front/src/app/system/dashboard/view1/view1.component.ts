import { Component, OnInit, ElementRef, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardService } from '../../../services/dashboard.services';
import * as Highcharts from 'highcharts';

@Component({
  selector: 'app-view1',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './view1.component.html',
  styleUrl: './view1.component.css'
})
export class View1Component implements OnInit {

  @ViewChild('trendEl') trendEl!: ElementRef<HTMLDivElement>;
  @ViewChild('statusEl') statusEl!: ElementRef<HTMLDivElement>;
  @ViewChild('officeEl') officeEl!: ElementRef<HTMLDivElement>;

  data: any = null;
  loading = true;
  error = '';
  today = new Date();

  private charts: Highcharts.Chart[] = [];

  constructor(private dashboardService: DashboardService) {}

  ngOnInit(): void {
    this.load();
  }

  get userName(): string {
    const u = this.data?.user;
    if (!u) return 'Usuario';
    const full = [u.first_name, u.last_name].filter(Boolean).join(' ');
    return full || 'Usuario';
  }

  get stats(): any {
    return this.data?.stats ?? {};
  }

  get summary(): any {
    return this.data?.summary ?? {};
  }

  get recentRecords(): any[] {
    return this.data?.recent_records ?? [];
  }

  get trend(): any {
    return this.data?.attendance_trend ?? { labels: [], data: [] };
  }

  get officeDist(): any {
    return this.data?.distribution_by_office ?? { labels: [], data: [] };
  }

  get statusToday(): any {
    return this.data?.attendance_status_today ?? { labels: [], data: [] };
  }

  get trendTotal(): number {
    return (this.trend.data as number[]).reduce((a, b) => a + b, 0);
  }

  get hasTrendData(): boolean {
    return this.trendTotal > 0;
  }

  get hasOfficeData(): boolean {
    return (this.officeDist.data as number[]).reduce((a, b) => a + b, 0) > 0;
  }

  get hasStatusData(): boolean {
    return (this.statusToday.data as number[]).reduce((a, b) => a + b, 0) > 0;
  }

  load(): void {
    this.loading = true;
    this.error = '';
    this.destroyCharts();

    this.dashboardService.getDashboard().subscribe({
      next: (res: any) => {
        this.data = res;
        this.loading = false;
        setTimeout(() => this.renderCharts(), 0);
      },
      error: () => {
        this.loading = false;
        this.error = 'No se pudieron cargar los datos del panel. Intenta de nuevo.';
      }
    });
  }

  refresh(): void {
    this.load();
  }

  initials(name: string): string {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    const first = parts[0]?.charAt(0) ?? '';
    const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';
    return (first + last).toUpperCase();
  }

  subtitle(record: any): string {
    return [record?.area, record?.office].filter((v) => !!v).join(' · ') || '—';
  }

  // ---------- Gráficas ----------

  private renderCharts(): void {
    this.destroyCharts();
    if (!this.data) return;

    const c = this.themeColors();

    if (this.trendEl) {
      this.charts.push(Highcharts.chart(this.trendEl.nativeElement, {
        chart: { type: 'area', height: 270, backgroundColor: 'transparent' },
        title: { text: undefined },
        credits: { enabled: false },
        accessibility: { enabled: false },
        xAxis: { categories: this.trend.labels, lineColor: c.grid, tickColor: c.grid, labels: { style: { color: c.body } } },
        yAxis: { gridLineColor: c.grid, min: 0, allowDecimals: false, labels: { style: { color: c.body } } },
        legend: { enabled: false },
        tooltip: { valueSuffix: ' registros' },
        plotOptions: { area: { lineWidth: 2, marker: { radius: 3 } } },
        series: [{ name: 'Asistencia', data: this.trend.data, color: c.primary, fillOpacity: 0.12 }]
      }));
    }

    if (this.statusEl) {
      const statusColors = [c.success, c.teal, c.red, c.blue];
      this.charts.push(Highcharts.chart(this.statusEl.nativeElement, {
        chart: { type: 'pie', height: 190, backgroundColor: 'transparent' },
        title: { text: undefined },
        credits: { enabled: false },
        accessibility: { enabled: false },
        plotOptions: {
          pie: {
            innerSize: '62%',
            dataLabels: { enabled: false },
            borderColor: c.surface,
            borderWidth: 2
          }
        },
        tooltip: { pointFormat: '<b>{point.y}</b> ({point.percentage:.1f}%)' },
        series: [{
          name: 'Registros',
          colorByPoint: true,
          data: this.statusToday.labels.map((label: string, i: number) => ({
            name: label,
            y: this.statusToday.data[i] ?? 0,
            color: statusColors[i] ?? c.body
          }))
        }]
      }));
    }

    if (this.officeEl) {
      this.charts.push(Highcharts.chart(this.officeEl.nativeElement, {
        chart: { type: 'bar', height: 220, backgroundColor: 'transparent' },
        title: { text: undefined },
        credits: { enabled: false },
        accessibility: { enabled: false },
        xAxis: { categories: this.officeDist.labels, lineColor: c.grid, tickColor: c.grid, labels: { style: { color: c.body } } },
        yAxis: { gridLineColor: c.grid, min: 0, allowDecimals: false, labels: { style: { color: c.body } } },
        legend: { enabled: false },
        tooltip: { pointFormat: '<b>{point.y}</b> empleados' },
        series: [{ name: 'Empleados', data: this.officeDist.data, color: c.teal, borderRadius: 4 }]
      }));
    }
  }

  private destroyCharts(): void {
    this.charts.forEach(chart => chart.destroy());
    this.charts = [];
  }

  private themeColors(): any {
    const root = getComputedStyle(document.documentElement);
    const read = (name: string, fallback: string) => {
      const v = root.getPropertyValue(name).trim();
      return v ? `rgb(${v})` : fallback;
    };
    return {
      primary: read('--primary-medium', '#006eeb'),
      teal: read('--accent-teal', '#00a4aa'),
      blue: read('--accent-blue', '#0096d0'),
      success: read('--success-green', '#00af81'),
      red: '#ef4444',
      title: read('--text-title', '#111827'),
      body: read('--text-body', '#6b7280'),
      surface: read('--surface', '#ffffff'),
      grid: 'rgba(120,127,140,0.2)'
    };
  }
}
