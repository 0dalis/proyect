import { DatePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MyProfile, MyProfileUpdate, MySummary } from '../../../core/models';
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

type Section = 'datos' | 'seguridad' | 'jornada' | 'apariencia' | 'empresa';

interface SectionItem {
  id: Section;
  label: string;
  icon: string;
}

@Component({
  selector: 'app-profile',
  imports: [
    DatePipe,
    SkeletonComponent,
    FormsModule,
    PageHeaderComponent,
    ModalComponent,
    AppearanceSettingsComponent,
    ImageUploadComponent,
  ],
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
  protected readonly profile = signal<MyProfile | null>(null);
  protected readonly uploading = signal(false);
  protected readonly initials = computed(() => initialsOf(this.auth.user()?.name ?? ''));

  protected readonly section = signal<Section>('datos');
  protected readonly sections = computed<SectionItem[]>(() => [
    { id: 'datos', label: 'Datos personales', icon: 'person-vcard' },
    { id: 'seguridad', label: 'Seguridad', icon: 'shield-lock' },
    ...(this.auth.user()?.employee
      ? [{ id: 'jornada' as const, label: 'Mi jornada', icon: 'calendar2-week' }]
      : []),
    { id: 'apariencia', label: 'Apariencia', icon: 'palette' },
    ...(this.auth.isOwner()
      ? [{ id: 'empresa' as const, label: 'Eliminar empresa', icon: 'exclamation-octagon' }]
      : []),
  ]);

  /** Formulario de datos personales (copia editable de `profile`). */
  protected form = { name: '', first_name: '', last_name: '', phone: '', email: '', current_password: '' };
  protected readonly saving = signal(false);

  protected password = { current_password: '', password: '', password_confirmation: '' };
  protected readonly showPassword = signal(false);
  protected readonly savingPassword = signal(false);
  protected pin = { current_password: '', pin: '', pin_confirmation: '' };
  protected readonly savingPin = signal(false);

  /** "Cerrar las demás sesiones": pide la contraseña en un modal. */
  protected readonly sessionsOpen = signal(false);
  protected readonly closingSessions = signal(false);
  protected sessionsPassword = '';

  /** "Eliminar perfil de empresa de AsistControl" (solo el dueño). */
  protected readonly deleting = signal(false);
  protected readonly deleteOpen = signal(false);
  protected deletion = { reason: '', password: '', confirmation: '' };
  protected readonly phrase = computed(() => `eliminar datos de ${this.auth.user()?.company.name ?? ''}`);

  async ngOnInit(): Promise<void> {
    try {
      await this.init();
    } finally {
      this.loading.set(false);
    }
  }

  private async init(): Promise<void> {
    const [profile, summary] = await Promise.all([
      this.profileService.profile().catch(() => null),
      this.auth.user()?.employee ? this.profileService.summary().catch(() => null) : null,
    ]);
    this.summary.set(summary);
    this.setProfile(profile);
  }

  private setProfile(profile: MyProfile | null): void {
    this.profile.set(profile);
    this.resetForm();
  }

  protected resetForm(): void {
    const p = this.profile();
    const user = this.auth.user();
    this.form = {
      name: p?.name ?? user?.name ?? '',
      first_name: p?.first_name ?? '',
      last_name: p?.last_name ?? '',
      phone: p?.phone ?? '',
      email: p?.email ?? user?.email ?? '',
      current_password: '',
    };
  }

  /** El correo es su usuario: si cambia, se pide la contraseña. */
  protected emailChanged(): boolean {
    const current = this.profile()?.email ?? this.auth.user()?.email ?? '';
    return this.form.email.trim().toLowerCase() !== current.toLowerCase();
  }

  protected dirty(): boolean {
    const p = this.profile();
    if (!p) {
      return false;
    }
    if (this.emailChanged()) {
      return true;
    }
    return p.linked_employee
      ? this.form.first_name.trim() !== (p.first_name ?? '') ||
          this.form.last_name.trim() !== (p.last_name ?? '') ||
          this.form.phone.trim() !== (p.phone ?? '')
      : this.form.name.trim() !== p.name;
  }

  protected canSave(): boolean {
    const p = this.profile();
    if (!p || !this.dirty() || this.saving() || !this.form.email.trim()) {
      return false;
    }
    if (this.emailChanged() && !this.form.current_password) {
      return false;
    }
    return p.linked_employee
      ? !!this.form.first_name.trim() && !!this.form.last_name.trim()
      : !!this.form.name.trim();
  }

  protected async saveProfile(): Promise<void> {
    const p = this.profile();
    if (!p || !this.canSave()) {
      return;
    }
    const data: MyProfileUpdate = p.linked_employee
      ? {
          first_name: this.form.first_name.trim(),
          last_name: this.form.last_name.trim(),
          phone: this.form.phone.trim() || null,
          email: this.form.email.trim(),
        }
      : { name: this.form.name.trim(), email: this.form.email.trim() };
    if (this.emailChanged()) {
      data.current_password = this.form.current_password;
    }

    this.saving.set(true);
    try {
      const { message, user } = await this.profileService.updateProfile(data);
      this.auth.replaceUser(user);
      this.setProfile(await this.profileService.profile().catch(() => null));
      this.toast.success(message, { title: 'Perfil guardado', icon: 'person-check' });
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se guardó tu perfil' });
    } finally {
      this.saving.set(false);
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

  /** Mismas reglas que el backend: 8+ caracteres, letras y números. */
  protected passwordRules(): { label: string; ok: boolean }[] {
    const value = this.password.password;
    return [
      { label: 'Al menos 8 caracteres', ok: value.length >= 8 },
      { label: 'Letras y números', ok: /[a-zA-Z]/.test(value) && /\d/.test(value) },
      {
        label: 'Las dos coinciden',
        ok: !!value && value === this.password.password_confirmation,
      },
    ];
  }

  protected canSavePassword(): boolean {
    return (
      !!this.password.current_password &&
      this.passwordRules().every((rule) => rule.ok) &&
      !this.savingPassword()
    );
  }

  protected async savePassword(): Promise<void> {
    this.savingPassword.set(true);
    try {
      const { message } = await this.profileService.updatePassword(this.password);
      this.toast.success(message, { title: 'Contraseña actualizada', icon: 'shield-check' });
      this.password = { current_password: '', password: '', password_confirmation: '' };
      this.profile.update((p) => (p ? { ...p, other_sessions: 0 } : p));
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se cambió la contraseña' });
    } finally {
      this.savingPassword.set(false);
    }
  }

  protected async savePin(): Promise<void> {
    this.savingPin.set(true);
    try {
      const { message } = await this.profileService.updatePin(this.pin);
      this.toast.success(message, { title: 'PIN actualizado', icon: 'key' });
      this.pin = { current_password: '', pin: '', pin_confirmation: '' };
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se cambió el PIN' });
    } finally {
      this.savingPin.set(false);
    }
  }

  protected openSessions(): void {
    this.sessionsPassword = '';
    this.sessionsOpen.set(true);
  }

  protected async closeOtherSessions(): Promise<void> {
    this.closingSessions.set(true);
    try {
      const { message } = await this.profileService.closeOtherSessions(this.sessionsPassword);
      this.sessionsOpen.set(false);
      this.profile.update((p) => (p ? { ...p, other_sessions: 0 } : p));
      this.toast.success(message, { title: 'Sesiones cerradas', icon: 'box-arrow-right' });
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se cerraron las sesiones' });
    } finally {
      this.closingSessions.set(false);
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
}
