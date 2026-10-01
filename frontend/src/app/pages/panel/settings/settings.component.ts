import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { CompanyService } from '../../../core/services/company.service';
import { errorMessage } from '../../../core/utils/error-message';
import { ImageUploadComponent } from '../../../shared/components/image-upload/image-upload.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { ToastService } from '../../../core/services/toast.service';
import { DialogService } from '../../../core/services/dialog.service';

@Component({
  selector: 'app-settings',
  imports: [FormsModule, PageHeaderComponent, ImageUploadComponent],
  templateUrl: './settings.component.html',
  styleUrl: './settings.component.scss',
})
export class SettingsComponent {
  private readonly dialog = inject(DialogService);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);
  private readonly companyService = inject(CompanyService);

  private readonly company = this.auth.user()!.company;
  protected readonly code = this.company.code;
  protected readonly uploadingLogo = signal(false);
  /** Inicial de la marca mientras no hay logotipo. */
  protected readonly companyInitial = computed(() => (this.company.name.trim()[0] ?? 'A').toUpperCase());
  protected settings = {
    name: this.company.name,
    employees_can_use_web: this.company.employees_can_use_web,
    payroll_enabled: this.company.payroll_enabled,
    bonuses_enabled: this.company.bonuses_enabled,
  };

  protected async uploadLogo(file: File): Promise<void> {
    this.uploadingLogo.set(true);
    try {
      const { logo_url } = await this.companyService.uploadLogo(file);
      this.auth.updateCompany({ logo_url });
      this.toast.success('Ya se ve en la marca del menú lateral.', {
        title: 'Logotipo actualizado',
        icon: 'image',
      });
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se pudo subir el logotipo' });
    } finally {
      this.uploadingLogo.set(false);
    }
  }

  protected async removeLogo(): Promise<void> {
    this.uploadingLogo.set(true);
    try {
      const { logo_url } = await this.companyService.removeLogo();
      this.auth.updateCompany({ logo_url });
      this.toast.info('La marca vuelve a mostrar la inicial de tu empresa.', {
        title: 'Logotipo eliminado',
        icon: 'image-alt',
      });
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se pudo quitar el logotipo' });
    } finally {
      this.uploadingLogo.set(false);
    }
  }

  protected async save(): Promise<void> {
    // Apagar un módulo oculta sus pantallas a todos: se confirma
    const turningOff = [
      this.company.payroll_enabled && !this.settings.payroll_enabled
        ? 'Sueldos (pre-nómina)'
        : null,
      this.company.bonuses_enabled && !this.settings.bonuses_enabled ? 'Bonos' : null,
    ].filter(Boolean);

    if (turningOff.length) {
      const confirmed = await this.dialog.confirm({
        title: `¿Desactivar ${turningOff.join(' y ')}?`,
        text: 'Nadie podrá entrar a esas pantallas hasta que lo vuelvas a activar. Los datos no se borran.',
        confirmText: 'Desactivar',
        variant: 'warning',
      });
      if (!confirmed) {
        return;
      }
    }

    try {
      await this.companyService.updateSettings(this.settings);
      this.auth.updateCompany(this.settings);
      this.toast.success('Los cambios ya aplican para toda la empresa.', {
        title: 'Configuración guardada',
        icon: 'gear-fill',
      });
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async copyCode(): Promise<void> {
    try {
      await navigator.clipboard.writeText(this.code);
      this.toast.success('Código copiado.', { icon: 'clipboard-check' });
    } catch {
      this.toast.info(`Código de empresa: ${this.code}`);
    }
  }
}
