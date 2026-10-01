import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ChoosePlanResult, PlanOptions } from '../../core/models';
import { AuthService } from '../../core/services/auth.service';
import { BillingService } from '../../core/services/billing.service';
import { ToastService } from '../../core/services/toast.service';
import { errorMessage } from '../../core/utils/error-message';
import { PlanCheckoutComponent } from '../../shared/components/plan-checkout/plan-checkout.component';
import { SkeletonComponent } from '../../shared/components/skeleton/skeleton.component';

type Step = 'terms' | 'plan' | 'done';

/**
 * Primera vez del dueño. No se puede cerrar: hasta terminar, el panel solo
 * muestra este modal (y Laravel solo acepta las rutas de onboarding).
 * 1. Autoriza el uso y confirma que leyó el aviso de privacidad y los términos.
 * 2. Elige uno de los 4 planes (mensual o anual) y registra su tarjeta en Stripe.
 * 3. Confirmación: prueba iniciada y fecha del primer cobro (o Free sin prueba).
 */
@Component({
  selector: 'app-onboarding-modal',
  imports: [FormsModule, RouterLink, CurrencyPipe, DatePipe, PlanCheckoutComponent, SkeletonComponent],
  templateUrl: './onboarding-modal.component.html',
  styleUrl: './onboarding-modal.component.scss',
})
export class OnboardingModalComponent implements OnInit {
  private readonly billing = inject(BillingService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  protected readonly options = signal<PlanOptions | null>(null);
  protected readonly step = signal<Step>('terms');
  protected readonly accepting = signal(false);
  protected readonly result = signal<ChoosePlanResult | null>(null);

  protected authorizeUse = false;
  protected acceptPrivacy = false;
  protected acceptTerms = false;

  protected readonly steps: { key: Step; label: string }[] = [
    { key: 'terms', label: 'Términos' },
    { key: 'plan', label: 'Plan y pago' },
    { key: 'done', label: 'Listo' },
  ];
  protected readonly stepIndex = computed(() => this.steps.findIndex((s) => s.key === this.step()));
  protected readonly ownerName = computed(() => this.auth.user()?.name.split(' ')[0] ?? '');

  async ngOnInit(): Promise<void> {
    try {
      const options = await this.billing.options('onboarding');
      this.options.set(options);
      if (options.accepted) {
        this.step.set('plan');
      }
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No pudimos cargar los planes' });
    }
  }

  protected async accept(): Promise<void> {
    this.accepting.set(true);
    try {
      await this.billing.accept();
      this.step.set('plan');
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.accepting.set(false);
    }
  }

  protected finished(result: ChoosePlanResult): void {
    this.result.set(result);
    this.step.set('done');
  }

  /** Cierra el modal: el usuario ya no está en onboarding y el panel se muestra completo. */
  protected async start(): Promise<void> {
    this.auth.user.set(this.result()!.user);
    await this.router.navigate(['/panel']);
  }

  protected price(result: ChoosePlanResult): number {
    return result.interval === 'year' ? result.plan.yearly_price : Number(result.plan.monthly_price);
  }

  protected async logout(): Promise<void> {
    await this.auth.logout().catch(() => undefined);
    await this.router.navigate(['/login']);
  }
}
