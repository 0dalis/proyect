import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import { AuthService } from '../../core/services/auth.service';
import { fakeUser } from '../../core/testing/fake-user';
import { FirstAccessModalComponent } from './first-access-modal.component';

describe('FirstAccessModalComponent', () => {
  let http: HttpTestingController;
  let auth: AuthService;

  const employee = {
    id: 5,
    name: 'Luis Pérez',
    employee_code: 'ALDE-0005',
    area_id: 1,
    office_id: 1,
    shift_id: 1,
    has_pin: false,
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    });
    http = TestBed.inject(HttpTestingController);
    auth = TestBed.inject(AuthService);
    auth.user.set(fakeUser({ role: 'employee', must_change_password: true, employee }));
    vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
  });

  afterEach(() => http.verify());

  const tick = () => new Promise((resolve) => setTimeout(resolve));

  async function fill(values: Record<string, string>) {
    const fixture = TestBed.createComponent(FirstAccessModalComponent);
    fixture.detectChanges();
    await fixture.whenStable();
    const el = fixture.nativeElement as HTMLElement;
    for (const [id, value] of Object.entries(values)) {
      const input = el.querySelector<HTMLInputElement>(`#${id}`)!;
      input.value = value;
      input.dispatchEvent(new Event('input'));
    }
    fixture.detectChanges();
    return { fixture, submit: el.querySelector<HTMLButtonElement>('button[type=submit]')! };
  }

  it('requires a new password and a 6 digit PIN', async () => {
    const { submit } = await fill({
      'fa-password': 'nueva1234',
      'fa-password-confirmation': 'nueva1234',
      'fa-pin': '1234',
      'fa-pin-confirmation': '1234',
    });

    expect(submit.disabled).toBe(true);
  });

  it('saves both and leaves the first access mode', async () => {
    const { submit } = await fill({
      'fa-password': 'nueva1234',
      'fa-password-confirmation': 'nueva1234',
      'fa-pin': '246810',
      'fa-pin-confirmation': '246810',
    });
    expect(submit.disabled).toBe(false);

    submit.click();
    const request = http.expectOne(`${environment.apiUrl}/me/first-access`);
    expect(request.request.body.pin).toBe('246810');
    request.flush({ message: 'Listo', user: fakeUser({ role: 'employee', must_change_password: false, employee }) });
    await tick();

    expect(auth.user()?.must_change_password).toBe(false);
  });
});
