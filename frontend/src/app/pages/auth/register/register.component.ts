import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { RecaptchaService } from '../../../core/services/recaptcha.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { RecaptchaNoticeComponent } from '../../../shared/components/recaptcha-notice/recaptcha-notice.component';
import { ThemeToggleComponent } from '../../../shared/components/theme-toggle/theme-toggle.component';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink, ThemeToggleComponent, RecaptchaNoticeComponent],
  host: { class: 'grid min-h-screen place-items-center px-4 py-10' },
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss',
})
export class RegisterComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly recaptcha = inject(RecaptchaService);

  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly sentTo = signal<string | null>(null);
  protected readonly resent = signal(false);

  protected readonly form = inject(FormBuilder).nonNullable.group({
    company_name: ['', Validators.required],
    name: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]],
    password_confirmation: ['', Validators.required],
    accept_terms: [false, Validators.requiredTrue],
  });

  ngOnInit(): void {
    // El plan se elige después de confirmar el correo, al entrar por primera vez
    void this.recaptcha.preload();
  }

  protected async submit(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);
    try {
      await this.auth.register(this.form.getRawValue());
      this.sentTo.set(this.form.controls.email.value);
      this.toast.success('Revisa tu correo para activar la cuenta.', {
        title: 'Cuenta creada',
        icon: 'envelope-check-fill',
      });
    } catch (error) {
      this.error.set(errorMessage(error));
      this.toast.error('Revisa los datos marcados en el formulario.', {
        title: 'No pudimos crear la cuenta',
      });
    } finally {
      this.loading.set(false);
    }
  }

  protected async resend(): Promise<void> {
    try {
      await this.auth.resendVerification(this.sentTo()!);
      this.resent.set(true);
      this.toast.info('Te enviamos un nuevo enlace de confirmación.');
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }
}
