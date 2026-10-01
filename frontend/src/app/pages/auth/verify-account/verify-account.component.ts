import { Component, inject, input, OnInit, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService, VerificationResult } from '../../../core/services/auth.service';
import { RecaptchaService } from '../../../core/services/recaptcha.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { RecaptchaNoticeComponent } from '../../../shared/components/recaptcha-notice/recaptcha-notice.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';
import { ThemeToggleComponent } from '../../../shared/components/theme-toggle/theme-toggle.component';

/**
 * Enlace del correo de confirmación (/verificar-cuenta/:id/:hash?expires&signature).
 * - Cuenta ya activa: al login con aviso.
 * - Enlace vencido: Laravel envía otro y se pide usar el más reciente.
 * - Pendiente: botón "Confirmar mi cuenta" (con reCAPTCHA v3).
 */
@Component({
  selector: 'app-verify-account',
  imports: [RouterLink, ThemeToggleComponent, RecaptchaNoticeComponent, SkeletonComponent],
  templateUrl: './verify-account.component.html',
  styleUrl: './verify-account.component.scss',
  host: { class: 'grid min-h-screen place-items-center px-4 py-10' },
})
export class VerifyAccountComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly recaptcha = inject(RecaptchaService);

  readonly id = input.required<string>();
  readonly hash = input.required<string>();
  readonly expires = input<string>();
  readonly signature = input<string>();

  protected readonly result = signal<VerificationResult | null>(null);
  protected readonly confirming = signal(false);

  async ngOnInit(): Promise<void> {
    void this.recaptcha.preload();
    try {
      await this.handle(await this.auth.verificationStatus(this.id(), this.hash(), this.query()));
    } catch (error) {
      this.result.set({ status: 'invalid', message: errorMessage(error), email: null });
    }
  }

  protected async confirm(): Promise<void> {
    this.confirming.set(true);
    try {
      await this.handle(await this.auth.confirmEmail(this.id(), this.hash(), this.query()));
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No pudimos confirmar tu cuenta' });
    } finally {
      this.confirming.set(false);
    }
  }

  private async handle(result: VerificationResult): Promise<void> {
    if (result.status === 'verified') {
      await this.router.navigate(['/login'], { queryParams: { verified: '1' } });
      return;
    }
    if (result.status === 'already_verified') {
      await this.router.navigate(['/login'], { queryParams: { verified: 'active' } });
      return;
    }
    this.result.set(result);
  }

  /** La firma de Laravel, tal cual vino en el enlace. */
  private query(): string {
    return new URLSearchParams({
      expires: this.expires() ?? '',
      signature: this.signature() ?? '',
    }).toString();
  }
}
