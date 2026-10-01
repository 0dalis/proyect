import { inject, Injectable } from '@angular/core';
import { BillingInterval, ChoosePlanResult, PlanOptions } from '../models';
import { ApiService } from './api.service';

/**
 * onboarding = modal de bienvenida (primera vez del dueño).
 * billing    = cambiar de plan después (por ejemplo, tras bajar a Free).
 */
export type PlanContext = 'onboarding' | 'billing';

export interface DeleteCompanyPayload {
  password: string;
  reason: string;
  confirmation: string;
}

@Injectable({ providedIn: 'root' })
export class BillingService {
  private readonly api = inject(ApiService);

  options(context: PlanContext): Promise<PlanOptions> {
    return this.api.get<PlanOptions>(context === 'onboarding' ? 'onboarding' : 'company/billing/plans');
  }

  /** Autoriza el uso, aviso de privacidad y términos (primer paso del modal). */
  accept(): Promise<{ accepted: boolean }> {
    return this.api.post('onboarding/accept', {
      authorize_use: true,
      accept_privacy: true,
      accept_terms: true,
    });
  }

  async setupIntent(context: PlanContext): Promise<string | null> {
    const path = context === 'onboarding' ? 'onboarding/setup-intent' : 'company/billing/setup-intent';
    return (await this.api.post<{ client_secret: string | null }>(path)).client_secret;
  }

  subscribe(
    context: PlanContext,
    plan: string,
    interval: BillingInterval,
    paymentMethod: string | null,
  ): Promise<ChoosePlanResult> {
    const path = context === 'onboarding' ? 'onboarding/subscribe' : 'company/billing/subscribe';
    return this.api.post<ChoosePlanResult>(path, { plan, interval, payment_method: paymentMethod });
  }

  /** "Eliminar perfil de empresa de AsistControl" (solo el dueño). */
  deleteCompany(payload: DeleteCompanyPayload): Promise<{ message: string; purge_after: string }> {
    return this.api.post('company/deletion', payload);
  }
}
