import { CurrencyPipe, DatePipe } from '@angular/common';
import {
  Component,
  computed,
  ElementRef,
  inject,
  input,
  OnDestroy,
  output,
  signal,
  viewChild,
} from '@angular/core';
import { BillingInterval, ChoosePlanResult, Plan, PlanOptions } from '../../../core/models';
import { BillingService, PlanContext } from '../../../core/services/billing.service';
import { CardForm, StripeService } from '../../../core/services/stripe.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';

/**
 * Elegir plan (mensual o anual, el anual cobra 11 meses) y registrar la
 * tarjeta en Stripe. Free no pide tarjeta ni tiene prueba.
 */
@Component({
  selector: 'app-plan-checkout',
  imports: [CurrencyPipe, DatePipe],
  templateUrl: './plan-checkout.component.html',
  styleUrl: './plan-checkout.component.scss',
})
export class PlanCheckoutComponent implements OnDestroy {
  private readonly billing = inject(BillingService);
  private readonly stripe = inject(StripeService);
  private readonly toast = inject(ToastService);

  readonly context = input.required<PlanContext>();
  readonly options = input.required<PlanOptions>();
  readonly completed = output<ChoosePlanResult>();

  private readonly cardHost = viewChild<ElementRef<HTMLElement>>('card');
  private cardForm: CardForm | null = null;

  protected readonly interval = signal<BillingInterval>('month');
  protected readonly selectedSlug = signal<string | null>(null);
  protected readonly cardLoading = signal(false);
  protected readonly cardComplete = signal(false);
  protected readonly cardError = signal<string | null>(null);
  protected readonly submitting = signal(false);

  protected readonly selected = computed(
    () => this.options().plans.find((plan) => plan.slug === this.selectedSlug()) ?? null,
  );
  protected readonly firstTime = computed(() => this.context() === 'onboarding');
  protected readonly demo = computed(() => this.options().billing_mode === 'demo');
  protected readonly needsCard = computed(() => !!this.selected() && !this.selected()!.is_free);

  /** Hoy y la fecha del primer cobro (un día antes de que termine la prueba). */
  protected readonly trialEnds = computed(() => this.addDays(this.selected()?.trial_days ?? 0));
  protected readonly chargeOn = computed(() =>
    this.addDays((this.selected()?.trial_days ?? 0) - 1),
  );

  protected readonly canSubmit = computed(
    () =>
      !!this.selected() &&
      !this.submitting() &&
      !this.isCurrent(this.selected()!) &&
      (!this.needsCard() || this.demo() || this.cardComplete()),
  );

  ngOnDestroy(): void {
    this.cardForm?.element.destroy();
  }

  protected price(plan: Plan): number {
    return this.interval() === 'year' ? plan.yearly_price : Number(plan.monthly_price);
  }

  protected monthlyEquivalent(plan: Plan): number {
    return plan.yearly_price / 12;
  }

  protected isCurrent(plan: Plan): boolean {
    return !this.firstTime() && plan.slug === this.options().current_plan;
  }

  protected async select(plan: Plan): Promise<void> {
    if (this.isCurrent(plan)) {
      return;
    }
    this.selectedSlug.set(plan.slug);
    if (!plan.is_free && !this.demo() && !this.cardForm) {
      await this.mountCard();
    }
  }

  protected async submit(): Promise<void> {
    const plan = this.selected();
    if (!plan) {
      return;
    }
    this.submitting.set(true);
    try {
      const paymentMethod = this.needsCard() && this.cardForm ? await this.cardForm.confirm() : null;
      const result = await this.billing.subscribe(
        this.context(),
        plan.slug,
        this.interval(),
        paymentMethod,
      );
      this.completed.emit(result);
    } catch (error) {
      this.toast.error(errorMessage(error), { title: 'No pudimos activar el plan' });
    } finally {
      this.submitting.set(false);
    }
  }

  private async mountCard(): Promise<void> {
    this.cardLoading.set(true);
    this.cardError.set(null);
    try {
      const secret = await this.billing.setupIntent(this.context());
      const key = this.options().stripe_key;
      // El contenedor aparece en cuanto hay un plan de pago elegido
      await new Promise((resolve) => setTimeout(resolve));
      const host = this.cardHost()?.nativeElement;
      if (!secret || !key || !host) {
        throw new Error('No se pudo preparar el formulario de pago.');
      }
      this.cardForm = await this.stripe.mountCardForm(key, secret, host);
      this.cardForm.element.on('change', (event) => this.cardComplete.set(event.complete));
    } catch (error) {
      this.cardError.set(errorMessage(error));
    } finally {
      this.cardLoading.set(false);
    }
  }

  private addDays(days: number): Date {
    const date = new Date();
    date.setDate(date.getDate() + days);
    return date;
  }
}
