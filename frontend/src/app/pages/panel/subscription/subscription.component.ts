import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { ChoosePlanResult, PlanOptions, Subscription } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { BillingService } from '../../../core/services/billing.service';
import { CompanyService } from '../../../core/services/company.service';
import { errorMessage } from '../../../core/utils/error-message';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { ProcessingService } from '../../../core/services/processing.service';
import { ToastService } from '../../../core/services/toast.service';
import { PlanCheckoutComponent } from '../../../shared/components/plan-checkout/plan-checkout.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

@Component({
  selector: 'app-subscription',
  imports: [SkeletonComponent, CurrencyPipe, DatePipe, PageHeaderComponent, PlanCheckoutComponent],
  templateUrl: './subscription.component.html',
  styleUrl: './subscription.component.scss',
})
export class SubscriptionComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly toast = inject(ToastService);
  private readonly processing = inject(ProcessingService);
  private readonly companyService = inject(CompanyService);
  private readonly billing = inject(BillingService);
  protected readonly auth = inject(AuthService);

  protected readonly sub = signal<Subscription | null>(null);
  /** Opciones para cambiar de plan (solo el dueño, al abrir la sección). */
  protected readonly planOptions = signal<PlanOptions | null>(null);
  protected readonly changingPlan = signal(false);

  async ngOnInit(): Promise<void> {
    try {
      await this.init();
    } finally {
      this.loading.set(false);
    }
  }

  private async init(): Promise<void> {
    try {
      this.sub.set(await this.companyService.subscription());
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async openPlanChange(): Promise<void> {
    this.changingPlan.set(true);
    try {
      this.planOptions.set(await this.processing.run(() => this.billing.options('billing')));
    } catch (error) {
      this.changingPlan.set(false);
      this.toast.error(errorMessage(error));
    }
  }

  protected async planChanged(result: ChoosePlanResult): Promise<void> {
    this.auth.user.set(result.user);
    this.changingPlan.set(false);
    this.planOptions.set(null);
    this.toast.success(`Tu empresa ya usa el plan ${result.plan.name}.`, {
      title: 'Plan actualizado',
    });
    await this.init();
  }

  protected usageItems(s: Subscription) {
    return [
      { label: 'Empleados activos', ...s.usage.employees },
      { label: 'Oficinas', ...s.usage.offices },
    ];
  }

  protected percent(used: number, limit: number): number {
    return limit ? Math.min(100, Math.round((used / limit) * 100)) : 0;
  }
}
