import { Component, inject, input, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { PublicConfigService } from '../../../core/services/public-config.service';
import { RecaptchaService } from '../../../core/services/recaptcha.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorCode, errorMessage } from '../../../core/utils/error-message';
import { RecaptchaNoticeComponent } from '../../../shared/components/recaptcha-notice/recaptcha-notice.component';
import { ThemeToggleComponent } from '../../../shared/components/theme-toggle/theme-toggle.component';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule, RouterLink, ThemeToggleComponent, RecaptchaNoticeComponent],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
  host: { class: 'grid min-h-screen place-items-center px-4 py-10' },
})
export class LoginComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly recaptcha = inject(RecaptchaService);
  private readonly config = inject(PublicConfigService);

  /** Viene de la página de confirmación de la cuenta (1 = recién confirmada, active = ya lo estaba). */
  readonly verified = input<string>();
  /** expired = el enlace de exportación de datos (baja de la empresa) venció. */
  readonly export = input<string>();
  readonly returnUrl = input<string>();

  protected readonly loading = signal(false);
  protected readonly unverified = signal(false);
  protected readonly resent = signal(false);

  protected readonly form = inject(FormBuilder).nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  ngOnInit(): void {
    void this.recaptcha.preload();

    if (this.verified() === '1') {
      this.toast.success('Inicia sesión para elegir tu plan y empezar.', {
        title: '¡Cuenta confirmada!',
        duration: 7000,
      });
    } else if (this.verified() === 'active') {
      this.toast.info('Tu cuenta ya está activa. Inicia sesión.', {
        title: 'Cuenta activa',
        icon: 'patch-check-fill',
        duration: 7000,
      });
    }

    if (this.export() === 'expired') {
      void this.exportExpired();
    }
  }

  private async exportExpired(): Promise<void> {
    const support = await this.config
      .load()
      .then((config) => config.support_email)
      .catch(() => 'soporte');
    this.toast.warning(
      `El enlace para descargar tus datos venció. Escribe a ${support} para recibir uno nuevo; solo es posible durante los 30 días posteriores a tu solicitud.`,
      { title: 'Enlace vencido', icon: 'clock-history', duration: 0 },
    );
  }

  protected async submit(): Promise<void> {
    this.loading.set(true);
    this.unverified.set(false);
    try {
      const { email, password } = this.form.getRawValue();
      const user = await this.auth.login(email, password);
      this.toast.success(`Entraste a ${user.company.name}.`, {
        title: `Bienvenido, ${user.name.split(' ')[0]}`,
        icon: 'person-check-fill',
      });
      const target = this.returnUrl()?.startsWith('/panel') ? this.returnUrl()! : '/panel';
      await this.router.navigateByUrl(target);
    } catch (error) {
      this.unverified.set(errorCode(error) === 'email_not_verified');
      this.toast.error(errorMessage(error), { title: 'No pudimos iniciar sesión' });
    } finally {
      this.loading.set(false);
    }
  }

  protected async resend(): Promise<void> {
    try {
      await this.auth.resendVerification(this.form.controls.email.value);
      this.resent.set(true);
      this.toast.info('Te enviamos un nuevo enlace de confirmación. Revisa tu correo.');
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }
}
