import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { CurrentUser } from '../../core/models';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { ToastService } from '../../core/services/toast.service';
import { errorMessage } from '../../core/utils/error-message';

/**
 * Primer acceso con la contraseña temporal del correo: el empleado elige su
 * contraseña y su PIN de 6 dígitos (el mismo del kiosko). No se puede cerrar:
 * hasta terminar, Laravel solo acepta esta ruta.
 */
@Component({
  selector: 'app-first-access-modal',
  imports: [FormsModule],
  templateUrl: './first-access-modal.component.html',
  styleUrl: './first-access-modal.component.scss',
})
export class FirstAccessModalComponent {
  private readonly api = inject(ApiService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  protected form = { password: '', password_confirmation: '', pin: '', pin_confirmation: '' };
  protected readonly saving = signal(false);

  protected readonly firstName = computed(() => this.auth.user()?.name.split(' ')[0] ?? '');
  protected readonly needsPin = computed(() => !!this.auth.user()?.employee);

  protected valid(): boolean {
    const { password, password_confirmation, pin, pin_confirmation } = this.form;
    const passwordOk =
      password.length >= 8 && /[a-zA-Z]/.test(password) && /\d/.test(password) && password === password_confirmation;
    const pinOk = !this.needsPin() || (/^\d{6}$/.test(pin) && pin === pin_confirmation);
    return passwordOk && pinOk;
  }

  protected async save(): Promise<void> {
    this.saving.set(true);
    try {
      const { message, user } = await this.api.post<{ message: string; user: CurrentUser }>(
        'me/first-access',
        this.needsPin() ? this.form : { ...this.form, pin: null, pin_confirmation: null },
      );
      this.auth.user.set(user);
      this.toast.success(message, { title: 'Cuenta lista', icon: 'shield-check' });
      await this.router.navigate(['/panel']);
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No se guardó' });
    } finally {
      this.saving.set(false);
    }
  }

  protected async logout(): Promise<void> {
    await this.auth.logout().catch(() => undefined);
    await this.router.navigate(['/login']);
  }
}
