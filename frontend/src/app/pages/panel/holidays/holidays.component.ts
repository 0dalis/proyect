import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Holiday } from '../../../core/models';
import { DialogService } from '../../../core/services/dialog.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

/**
 * Días festivos: no cuentan como falta y, si se trabajan, la pre-nómina paga
 * el día doble adicional (art. 75 LFT). Los oficiales se cargan con un botón.
 */
@Component({
  selector: 'app-holidays',
  imports: [FormsModule, PageHeaderComponent, SkeletonComponent],
  templateUrl: './holidays.component.html',
  styleUrl: './holidays.component.scss',
})
export class HolidaysComponent implements OnInit {
  private readonly organization = inject(OrganizationService);
  private readonly toast = inject(ToastService);
  private readonly dialog = inject(DialogService);

  protected readonly year = signal(new Date().getFullYear());
  protected readonly holidays = signal<Holiday[]>([]);
  protected readonly loading = signal(true);
  protected readonly busy = signal(false);
  protected draft = { date: '', name: '' };

  async ngOnInit(): Promise<void> {
    await this.load();
  }

  protected async changeYear(delta: number): Promise<void> {
    this.year.update((year) => year + delta);
    await this.load();
  }

  protected async loadOfficial(): Promise<void> {
    this.busy.set(true);
    try {
      const { added, holidays } = await this.organization.loadOfficialHolidays(this.year());
      this.holidays.set(holidays);
      this.toast.success(
        added ? `Agregamos ${added} días de descanso obligatorio.` : 'Ya estaban todos los oficiales.',
        { title: `Festivos ${this.year()}`, icon: 'calendar-check' },
      );
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.busy.set(false);
    }
  }

  protected async add(): Promise<void> {
    this.busy.set(true);
    try {
      await this.organization.addHoliday(this.draft.date, this.draft.name.trim());
      this.toast.success(`${this.draft.name.trim()} agregado.`);
      this.draft = { date: '', name: '' };
      await this.load();
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se agregó' });
    } finally {
      this.busy.set(false);
    }
  }

  protected async remove(holiday: Holiday): Promise<void> {
    const confirmed = await this.dialog.confirm({
      title: `¿Quitar ${holiday.name}?`,
      text: holiday.is_official
        ? 'Es un descanso obligatorio de la Ley Federal del Trabajo. Si lo quitas, contará como día laboral.'
        : 'Ese día volverá a contar como laboral.',
      confirmText: 'Quitar',
      variant: 'danger',
    });
    if (!confirmed) {
      return;
    }
    try {
      await this.organization.deleteHoliday(holiday.id);
      this.holidays.update((list) => list.filter((h) => h.id !== holiday.id));
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  /** "2026-09-16" → "miércoles 16 de septiembre" (sin desfase por zona horaria). */
  protected label(date: string): string {
    const [y, m, d] = date.slice(0, 10).split('-').map(Number);
    return new Intl.DateTimeFormat('es-MX', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      timeZone: 'UTC',
    }).format(new Date(Date.UTC(y, m - 1, d)));
  }

  private async load(): Promise<void> {
    this.loading.set(true);
    try {
      this.holidays.set(await this.organization.holidays(this.year()));
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.loading.set(false);
    }
  }
}
