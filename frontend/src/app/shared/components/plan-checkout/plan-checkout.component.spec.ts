import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { LOCALE_ID } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es-MX';
import { environment } from '../../../../environments/environment';
import { Plan, PlanOptions } from '../../../core/models';
import { PlanCheckoutComponent } from './plan-checkout.component';

registerLocaleData(localeEs);

function plan(overrides: Partial<Plan>): Plan {
  return {
    name: 'Plus',
    slug: 'plus',
    description: null,
    monthly_price: '1299.00',
    included_employees: 75,
    included_offices: 3,
    employee_block_size: 25,
    employee_block_price: '300.00',
    extra_office_price: '149.00',
    features: [],
    includes_payroll: true,
    is_free: false,
    yearly_price: 14289,
    database_tier: 'plus',
    database_label: 'Plus',
    trial_days: 14,
    ...overrides,
  };
}

const OPTIONS: PlanOptions = {
  company_name: 'Panadería Sol',
  accepted: true,
  legal_version: '2026-09',
  billing_mode: 'demo',
  stripe_key: null,
  current_plan: 'free',
  plans: [
    plan({ name: 'Free', slug: 'free', monthly_price: '0.00', yearly_price: 0, is_free: true, trial_days: 0, includes_payroll: false }),
    plan({}),
  ],
};

describe('PlanCheckoutComponent', () => {
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: LOCALE_ID, useValue: 'es-MX' },
      ],
    });
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  const tick = () => new Promise((resolve) => setTimeout(resolve));

  function create(context: 'onboarding' | 'billing' = 'onboarding') {
    const fixture = TestBed.createComponent(PlanCheckoutComponent);
    fixture.componentRef.setInput('context', context);
    fixture.componentRef.setInput('options', OPTIONS);
    fixture.detectChanges();
    return fixture;
  }

  const planButtons = (el: HTMLElement) => Array.from(el.querySelectorAll<HTMLButtonElement>('button.plan'));
  const submitButton = (el: HTMLElement) => el.querySelector<HTMLButtonElement>('button.btn-primary')!;

  it('shows the yearly price as 11 months', () => {
    const fixture = create();
    const el = fixture.nativeElement as HTMLElement;

    el.querySelectorAll<HTMLButtonElement>('.interval button')[1].click();
    fixture.detectChanges();

    expect(el.textContent).toContain('14,289');
    expect(el.textContent).toContain('/ año');
  });

  it('explains the trial and that the charge happens one day before it ends', async () => {
    const fixture = create();
    const el = fixture.nativeElement as HTMLElement;

    planButtons(el)[1].click();
    await tick();
    fixture.detectChanges();

    expect(el.textContent).toContain('Hoy no se te cobra nada');
    expect(el.textContent).toContain('Un día antes');
    expect(submitButton(el).textContent).toContain('Comenzar prueba gratis');
  });

  it('subscribes to Free without a card and emits the result', async () => {
    const fixture = create();
    const el = fixture.nativeElement as HTMLElement;
    const completed = vi.fn();
    fixture.componentInstance.completed.subscribe(completed);

    planButtons(el)[0].click();
    await tick();
    fixture.detectChanges();
    expect(el.textContent).toContain('no incluye pre-nómina ni bonos');

    submitButton(el).click();
    const request = http.expectOne(`${environment.apiUrl}/onboarding/subscribe`);
    expect(request.request.body).toEqual({ plan: 'free', interval: 'month', payment_method: null });
    request.flush({ status: 'active' });
    await tick();

    expect(completed).toHaveBeenCalledWith({ status: 'active' });
  });

  it('does not offer the current plan when changing plans later', () => {
    const fixture = create('billing');
    const [free] = planButtons(fixture.nativeElement);

    expect(free.disabled).toBe(true);
    expect(free.textContent).toContain('Plan actual');
  });
});
