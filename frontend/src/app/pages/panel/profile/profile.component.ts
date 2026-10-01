import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MySummary } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { BillingService } from '../../../core/services/billing.service';
import { ProfileService } from '../../../core/services/profile.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { AppearanceSettingsComponent } from '../../../shared/components/appearance-settings/appearance-settings.component';
import { ImageUploadComponent, initialsOf } from '../../../shared/components/image-upload/image-upload.component';
import { ModalComponent } from '../../../shared/components/modal/modal.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { WEEKDAYS } from '../../../shared/constants/labels';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

@Component({
  selector: 'app-profile',
  imports: [SkeletonComponent, FormsModule, PageHeaderComponent, ModalComponent, AppearanceSettingsComponent, ImageUploadComponent],
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.scss',
})
export class ProfileComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  protected readonly auth = inject(AuthService);
  private readonly profileService = inject(ProfileService);
  private readonly toast = inject(ToastService);
  private readonly billing = inject(BillingService);
  private readonly router = inject(Router);

  protected readonly summary = signal<MySummary | null>(null);
  protected readonly uploading = signal(false);
  protected readonly initials = computed(() => initialsOf(this.auth.user()?.name ?? ''));

  /** "Eliminar perfil de empresa de AsistControl" (solo el dueño). */
  protected readonly deleting = signal(false);
  protected readonly deleteOpen = signal(false);
  protected deletion = { reason: '', password: '', confirmation: '' };
  protected readonly phrase = computed(() => `eliminar datos de ${this.auth.user()?.company.name ?? ''}`);

  protected password = { current_password: '', password: '', password_confirmation: '' };
  protected pin = { current_password: '', pin: '', pin_confirmation: '' };

  async ngOnInit(): Promise<void> {
    try {
      await this.init();
    } finally {
      this.loading.set(false);
    }
  }

  private async init(): Promise<void> {
    if (this.auth.user()?.employee) {
      this.summary.set(await this.profileService.summary().catch(() => null));
    }
  }

  protected days(weekdays: number[]): string {
    return WEEKDAYS.filter((d) => weekdays.includes(d.value))
      .map((d) => d.label.slice(0, 3))
      .join(', ');
  }

  protected async uploadAvatar(file: File): Promise<void> {
    this.uploading.set(true);
    try {
      const { user } = await this.profileService.uploadAvatar(file);
      this.auth.updateAvatar(user.avatar_url);
      this.toast.success('Se ve en la barra superior, junto a tu nombre.', {
        title: 'Foto actualizada',
        icon: 'person-bounding-box',
      });
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se pudo subir la foto' });
    } finally {
      this.uploading.set(false);
    }
  }

  protected async removeAvatar(): Promise<void> {
    this.uploading.set(true);
    try {
      const { user } = await this.profileService.removeAvatar();
      this.auth.updateAvatar(user.avatar_url);
      this.toast.info('Volviste a tus iniciales.', { title: 'Foto eliminada', icon: 'person-x' });
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se pudo quitar la foto' });
    } finally {
      this.uploading.set(false);
    }
  }

  protected async savePassword(): Promise<void> {
    try {
      const { message } = await this.profileService.updatePassword(this.password);
      this.toast.success(message, { title: 'Contraseña actualizada', icon: 'shield-check' });
      this.password = { current_password: '', password: '', password_confirmation: '' };
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se cambió la contraseña' });
    }
  }

  /** Igual que Laravel: sin distinguir mayúsculas ni espacios repetidos. */
  protected phraseMatches(): boolean {
    const normalize = (value: string) => value.trim().replace(/\s+/g, ' ').toLowerCase();
    return normalize(this.deletion.confirmation) === normalize(this.phrase());
  }

  protected openDeletion(): void {
    this.deletion = { reason: '', password: '', confirmation: '' };
    this.deleteOpen.set(true);
  }

  protected async deleteCompany(): Promise<void> {
    this.deleting.set(true);
    try {
      const { message } = await this.billing.deleteCompany(this.deletion);
      this.deleteOpen.set(false);
      this.auth.clear();
      this.toast.info(message, { title: 'Solicitud recibida', icon: 'envelope-check', duration: 0 });
      await this.router.navigate(['/login']);
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se pudo solicitar la eliminación' });
    } finally {
      this.deleting.set(false);
    }
  }

  protected async savePin(): Promise<void> {
    try {
      const { message } = await this.profileService.updatePin(this.pin);
      this.toast.success(message, { title: 'PIN actualizado', icon: 'key' });
      this.pin = { current_password: '', pin: '', pin_confirmation: '' };
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se cambió el PIN' });
    }
  }
}
