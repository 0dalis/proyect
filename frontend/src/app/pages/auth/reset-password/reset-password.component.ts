import { Component, inject, input, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { RecaptchaService } from '../../../core/services/recaptcha.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { RecaptchaNoticeComponent } from '../../../shared/components/recaptcha-notice/recaptcha-notice.component';
import { ThemeToggleComponent } from '../../../shared/components/theme-toggle/theme-toggle.component';

/**
 * Enlace del correo "Restablece tu contraseña" (/restablecer-contrasena?token&email).
 */
@Component({
  selector: 'app-reset-password',
  imports: [FormsModule, RouterLink, ThemeToggleComponent, RecaptchaNoticeComponent],
  templateUrl: './reset-password.component.html',
  styleUrl: './reset-password.component.scss',
  host: { class: 'grid min-h-screen place-items-center px-4 py-10' },
})
export class ResetPasswordComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly recaptcha = inject(RecaptchaService);

  readonly token = input<string>();
  readonly email = input<string>();

  protected password = '';
  protected confirmation = '';
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);

  ngOnInit(): void {
    void this.recaptcha.preload();
  }

  protected valid(): boolean {
    return (
      this.password.length >= 8 &&
      /[a-zA-Z]/.test(this.password) &&
      /\d/.test(this.password) &&
      this.password === this.confirmation
    );
  }

  protected async save(): Promise<void> {
    this.saving.set(true);
    this.error.set(null);
    try {
      const { message } = await this.auth.resetPassword({
        token: this.token() ?? '',
        email: this.email() ?? '',
        password: this.password,
        password_confirmation: this.confirmation,
      });
      this.toast.success(message, { title: 'Contraseña actualizada', icon: 'shield-check' });
      await this.router.navigate(['/login']);
    } catch (error) {
      this.error.set(errorMessage(error));
    } finally {
      this.saving.set(false);
    }
  }
}
