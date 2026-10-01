import { Component, Input, OnChanges } from '@angular/core';
import { CommonModule } from '@angular/common';

interface CalendarDay {
  date: Date;
  inMonth: boolean;
  key: string;
}

@Component({
  selector: 'app-calendar',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="cal">
      <div class="flex items-center justify-between mb-3">
        <button type="button" (click)="prevMonth()" class="btn-secondary"><i class="bi bi-chevron-left"></i></button>
        <p class="text-sm font-bold text-text-title capitalize">{{ monthLabel }}</p>
        <button type="button" (click)="nextMonth()" class="btn-secondary"><i class="bi bi-chevron-right"></i></button>
      </div>

      <div class="grid grid-cols-7 gap-1 mb-1">
        <span *ngFor="let w of weekLabels" class="text-center text-[10px] font-semibold uppercase text-text-body py-1">{{ w }}</span>
      </div>

      <div class="grid grid-cols-7 gap-1">
        <button type="button" *ngFor="let c of cells"
          class="cal-cell"
          [class.cal-out]="!c.inMonth"
          [ngClass]="cellClass(c)"
          (click)="select(c)"
          [title]="tooltip(c)">
          <span class="cal-day">{{ c.date.getDate() }}</span>
          <span class="cal-dot" *ngIf="cellType(c)"></span>
        </button>
      </div>

      <div class="flex flex-wrap gap-3 mt-3 text-[10px] text-text-body">
        <span class="flex items-center gap-1"><span class="cal-legend" style="background:#16a34a"></span> Presente</span>
        <span class="flex items-center gap-1"><span class="cal-legend" style="background:#d97706"></span> Retardo</span>
        <span class="flex items-center gap-1"><span class="cal-legend" style="background:#dc2626"></span> Falta</span>
        <span class="flex items-center gap-1"><span class="cal-legend" style="background:#4f46e5"></span> Vacaciones</span>
        <span class="flex items-center gap-1"><span class="cal-legend" style="background:#0ea5e9"></span> Festivo</span>
        <span class="flex items-center gap-1"><span class="cal-legend" style="background:#94a3b8"></span> Descanso</span>
      </div>
    </div>
  `,
  styles: [`
    :host { display: block; }
    .cal-cell {
      position: relative;
      aspect-ratio: 1 / 1;
      border-radius: 0.5rem;
      border: 1px solid rgba(120,127,140,0.15);
      background: var(--color-surface);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      color: var(--color-text-title);
      cursor: pointer;
      transition: all 0.12s ease;
    }
    .cal-cell:hover { border-color: var(--color-primary-medium); }
    .cal-out { opacity: 0.3; }
    .cal-day { font-weight: 600; }
    .cal-dot {
      position: absolute;
      bottom: 4px;
      width: 5px;
      height: 5px;
      border-radius: 9999px;
      background: currentColor;
    }
    .cal-legend { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
    .cal-present { background: rgba(22,163,74,0.12); color: #16a34a; border-color: rgba(22,163,74,0.35); }
    .cal-late { background: rgba(217,119,6,0.12); color: #d97706; border-color: rgba(217,119,6,0.35); }
    .cal-absent { background: rgba(220,38,38,0.12); color: #dc2626; border-color: rgba(220,38,38,0.35); }
    .cal-vacation { background: rgba(79,70,229,0.14); color: #4f46e5; border-color: rgba(79,70,229,0.4); }
    .cal-justified { background: rgba(120,127,140,0.14); color: var(--color-text-body); }
    .cal-holiday { background: rgba(14,165,233,0.12); color: #0ea5e9; border-color: rgba(14,165,233,0.35); }
    .cal-rest { color: var(--color-text-body); opacity: 0.55; }
  `]
})
export class CalendarComponent implements OnChanges {

  @Input() days: any[] = [];
  @Input() holidays: { date: string; name?: string }[] = [];
  @Input() workDays: number[] = [1, 2, 3, 4, 5];

  weekLabels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

  current = new Date();

  private dayMap = new Map<string, any>();
  private holidayMap = new Map<string, string>();

  ngOnChanges(): void {
    this.dayMap = new Map((this.days ?? []).map((d) => [this.key(d.date), d]));
    this.holidayMap = new Map((this.holidays ?? []).map((h) => [this.key(h.date), h.name ?? 'Festivo']));
  }

  get monthLabel(): string {
    return this.current.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
  }

  get cells(): CalendarDay[] {
    const year = this.current.getFullYear();
    const month = this.current.getMonth();
    const first = new Date(year, month, 1);
    const offset = (first.getDay() + 6) % 7; // Lunes = 0
    const start = new Date(year, month, 1 - offset);

    return Array.from({ length: 42 }, (_, i) => {
      const date = new Date(start);
      date.setDate(start.getDate() + i);
      return { date, inMonth: date.getMonth() === month, key: this.key(date) };
    });
  }

  prevMonth(): void {
    this.current = new Date(this.current.getFullYear(), this.current.getMonth() - 1, 1);
  }

  nextMonth(): void {
    this.current = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 1);
  }

  select(cell: CalendarDay): void {
    // Reservado para acciones futuras (selección de rango).
  }

  cellType(cell: CalendarDay): string | null {
    if (this.holidayMap.has(cell.key)) return 'holiday';
    const day = this.dayMap.get(cell.key);
    if (day) {
      if (day.leave_type === 'vacation') return 'vacation';
      if (day.status === 'present') return 'present';
      if (day.status === 'late') return 'late';
      if (day.status === 'absent') return 'absent';
      if (day.status === 'justified') return 'justified';
    }
    if (cell.inMonth && !this.workDays.includes(cell.date.getDay() === 0 ? 7 : cell.date.getDay())) {
      return 'rest';
    }
    return null;
  }

  cellClass(cell: CalendarDay): any {
    const type = this.cellType(cell);
    return {
      'cal-present': type === 'present',
      'cal-late': type === 'late',
      'cal-absent': type === 'absent',
      'cal-vacation': type === 'vacation',
      'cal-justified': type === 'justified',
      'cal-holiday': type === 'holiday',
      'cal-rest': type === 'rest',
    };
  }

  tooltip(cell: CalendarDay): string {
    if (this.holidayMap.has(cell.key)) return this.holidayMap.get(cell.key) ?? '';
    const day = this.dayMap.get(cell.key);
    if (day?.leave_type === 'vacation') return 'Vacaciones (con goce)';
    return day ? day.status : '';
  }

  private key(value: any): string {
    if (value instanceof Date) {
      return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
    }
    return String(value).slice(0, 10);
  }
}
