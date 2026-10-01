import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Kiosk, Office } from '../../../core/models';
import { KioskService } from '../../../core/services/kiosk.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { errorMessage } from '../../../core/utils/error-message';
import { ModalComponent } from '../../../shared/components/modal/modal.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { ToastService } from '../../../core/services/toast.service';
import { DialogService } from '../../../core/services/dialog.service';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

@Component({
  selector: 'app-kiosks',
  imports: [SkeletonComponent, FormsModule, DatePipe, ModalComponent, PageHeaderComponent],
  templateUrl: './kiosks.component.html',
  styleUrl: './kiosks.component.scss',
})
export class KiosksComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly dialog = inject(DialogService);
  private readonly toast = inject(ToastService);
  private readonly kioskService = inject(KioskService);
  private readonly organizationService = inject(OrganizationService);

  protected readonly kioskUrl = `${location.origin}/kiosko`;
  protected readonly kiosks = signal<Kiosk[]>([]);
  protected readonly offices = signal<Office[]>([]);
  protected readonly creating = signal(false);
  protected readonly newToken = signal<string | null>(null);
  protected readonly copied = signal(false);
  protected draft = { name: '', office_id: 0 };

  async ngOnInit(): Promise<void> {
    this.offices.set(await this.organizationService.offices());
    this.draft.office_id = this.offices()[0]?.id ?? 0;
    await this.load();
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    this.kiosks.set(await this.kioskService.list());
  }

  protected async create(): Promise<void> {
    try {
      const kiosk = await this.kioskService.create(this.draft.name, this.draft.office_id);
      this.creating.set(false);
      this.showToken(kiosk.token!);
      this.toast.success('Copia el código de activación antes de salir de esta pantalla.', {
        title: `Kiosko "${kiosk.name}" creado`,
        icon: 'display',
      });
      await this.load();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async regenerate(kiosk: Kiosk): Promise<void> {
    const confirmed = await this.dialog.confirm({
      title: `¿Generar un código nuevo para "${kiosk.name}"?`,
      text: 'El dispositivo que usa el código actual se desconectará y habrá que activarlo de nuevo.',
      confirmText: 'Generar código nuevo',
      variant: 'warning',
    });
    if (!confirmed) {
      return;
    }
    try {
      const { token } = await this.kioskService.regenerateToken(kiosk.id);
      this.showToken(token);
      this.toast.success('Actívalo en el dispositivo con el código nuevo.', {
        title: 'Código regenerado',
        icon: 'key',
      });
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async toggle(kiosk: Kiosk): Promise<void> {
    if (kiosk.is_active) {
      const confirmed = await this.dialog.confirm({
        title: `¿Desactivar "${kiosk.name}"?`,
        text: 'Nadie podrá checar en ese kiosko hasta que lo actives de nuevo.',
        confirmText: 'Desactivar',
        variant: 'danger',
      });
      if (!confirmed) {
        return;
      }
    }
    try {
      await this.kioskService.setActive(kiosk.id, !kiosk.is_active);
      this.toast.success(kiosk.name, {
        title: kiosk.is_active ? 'Kiosko desactivado' : 'Kiosko activado',
      });
      await this.load();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async copy(token: string): Promise<void> {
    try {
      await navigator.clipboard.writeText(token);
      this.copied.set(true);
      this.toast.info('Pégalo en la pantalla del kiosko.', {
        title: 'Código copiado',
        icon: 'clipboard-check',
        duration: 2500,
      });
    } catch {
      this.toast.warning('Tu navegador no permitió copiar. Selecciona el código y cópialo a mano.');
    }
  }

  private showToken(token: string): void {
    this.copied.set(false);
    this.newToken.set(token);
  }
}
