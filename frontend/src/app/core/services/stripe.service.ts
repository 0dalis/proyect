import { DOCUMENT } from '@angular/common';
import { inject, Injectable } from '@angular/core';

/** Lo mínimo de Stripe.js que usa el panel. */
export interface StripeElement {
  mount(target: HTMLElement): void;
  destroy(): void;
  on(event: 'change', handler: (event: { complete: boolean }) => void): void;
}

interface StripeElements {
  create(type: 'payment', options?: Record<string, unknown>): StripeElement;
}

interface StripeInstance {
  elements(options: Record<string, unknown>): StripeElements;
  confirmSetup(options: {
    elements: StripeElements;
    redirect: 'if_required';
    confirmParams?: Record<string, unknown>;
  }): Promise<{
    setupIntent?: { payment_method: string | { id: string } };
    error?: { message?: string };
  }>;
}

declare global {
  interface Window {
    Stripe?: (key: string, options?: Record<string, unknown>) => StripeInstance;
  }
}

export interface CardForm {
  element: StripeElement;
  /** Confirma la tarjeta con Stripe y devuelve el id del método de pago (pm_...). */
  confirm(): Promise<string>;
}

/**
 * Formulario de tarjeta de Stripe (Payment Element). Los datos de la tarjeta
 * van directo a Stripe; Laravel solo recibe el id del método de pago.
 */
@Injectable({ providedIn: 'root' })
export class StripeService {
  private readonly document = inject(DOCUMENT);
  private script: Promise<void> | null = null;

  async mountCardForm(
    publishableKey: string,
    clientSecret: string,
    target: HTMLElement,
  ): Promise<CardForm> {
    await this.load();
    const stripe = window.Stripe!(publishableKey, { locale: 'es-419' });
    const dark = this.document.documentElement.classList.contains('dark');
    const elements = stripe.elements({
      clientSecret,
      appearance: {
        theme: dark ? 'night' : 'stripe',
        variables: { colorPrimary: '#3B82F6', borderRadius: '10px', fontFamily: 'inherit' },
      },
    });
    const element = elements.create('payment', { layout: 'tabs' });
    element.mount(target);

    return {
      element,
      confirm: async () => {
        const result = await stripe.confirmSetup({ elements, redirect: 'if_required' });
        if (result.error || !result.setupIntent) {
          throw new Error(result.error?.message ?? 'Stripe no pudo validar la tarjeta.');
        }
        const method = result.setupIntent.payment_method;
        return typeof method === 'string' ? method : method.id;
      },
    };
  }

  private load(): Promise<void> {
    this.script ??= new Promise<void>((resolve, reject) => {
      if (window.Stripe) {
        resolve();
        return;
      }
      const script = this.document.createElement('script');
      script.src = 'https://js.stripe.com/v3/';
      script.onload = () => resolve();
      script.onerror = () => {
        this.script = null;
        reject(new Error('No se pudo cargar Stripe.'));
      };
      this.document.head.appendChild(script);
    });
    return this.script;
  }
}
