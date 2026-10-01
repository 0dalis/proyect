import { Component, inject, input, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { RecaptchaService } from '../../../core/services/recaptcha.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { RecaptchaNoticeComponent } from '../../../shared/components/recaptcha-notice/recaptcha-notice.component';
import { ThemeToggleComponent } from '../../../shared/components/theme-toggle/theme-toggle.component';

/**
 * Olvidé mi contraseña: pide el correo y envía el enlace (60 minutos).
 */
@Component({
  selector: 'app-forgot-password',
  imports: [FormsModule, RouterLink, ThemeToggleComponent, RecaptchaNoticeComponent],
  templateUrl: './forgot-password.component.html',
  styleUrl: './forgot-password.component.scss',
  host: { class: 'grid min-h-screen place-items-center px-4 py-10' },
})
export class ForgotPasswordComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly recaptcha = inject(RecaptchaService);

  /** Llega desde el login con el correo ya escrito. */
  readonly email = input<string>();

  protected address = '';
  protected readonly sending = signal(false);
  protected readonly sent = signal(false);

  ngOnInit(): void {
    this.address = this.email() ?? '';
    void this.recaptcha.preload();
  }

  protected async send(): Promise<void> {
    this.sending.set(true);
    try {
      await this.auth.forgotPassword(this.address.trim());
      this.sent.set(true);
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No pudimos enviar el enlace' });
    } finally {
      this.sending.set(false);
    }
  }
}
