import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { environment } from '../../../../environments/environment';
import { ResetPasswordComponent } from './reset-password.component';

describe('ResetPasswordComponent', () => {
  let http: HttpTestingController;
  let navigate: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    });
    http = TestBed.inject(HttpTestingController);
    navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
  });

  afterEach(() => http.verify());

  const tick = () => new Promise((resolve) => setTimeout(resolve));

  async function open(token?: string, email?: string) {
    const fixture = TestBed.createComponent(ResetPasswordComponent);
    if (token) fixture.componentRef.setInput('token', token);
    if (email) fixture.componentRef.setInput('email', email);
    fixture.detectChanges();
    http.expectOne(`${environment.apiUrl}/config`).flush({ recaptcha_site_key: null });
    await fixture.whenStable();
    return fixture;
  }

  it('asks for a new link when the email link is incomplete', async () => {
    const fixture = await open();

    expect(fixture.nativeElement.textContent).toContain('Enlace incompleto');
  });

  it('sends the token with the new password and goes to the login', async () => {
    const fixture = await open('tok123', 'ana@luna.test');
    const el = fixture.nativeElement as HTMLElement;
    for (const id of ['reset-password', 'reset-confirmation']) {
      const input = el.querySelector<HTMLInputElement>(`#${id}`)!;
      input.value = 'nueva1234';
      input.dispatchEvent(new Event('input'));
    }
    fixture.detectChanges();

    el.querySelector<HTMLButtonElement>('button[type=submit]')!.click();
    await tick();
    http.expectOne(environment.csrfCookieUrl).flush('');
    await tick();
    const request = http.expectOne(`${environment.apiUrl}/auth/password/reset`);
    expect(request.request.body).toMatchObject({
      token: 'tok123',
      email: 'ana@luna.test',
      password: 'nueva1234',
      password_confirmation: 'nueva1234',
    });
    request.flush({ message: 'Listo' });
    await tick();

    expect(navigate).toHaveBeenCalledWith(['/login']);
  });
});
