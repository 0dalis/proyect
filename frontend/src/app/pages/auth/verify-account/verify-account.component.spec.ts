import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { environment } from '../../../../environments/environment';
import { VerifyAccountComponent } from './verify-account.component';

describe('VerifyAccountComponent', () => {
  let http: HttpTestingController;
  let navigate: ReturnType<typeof vi.spyOn>;

  const statusUrl = `${environment.apiUrl}/auth/email/verify/7/abc?expires=123&signature=sig`;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    });
    http = TestBed.inject(HttpTestingController);
    navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
  });

  afterEach(() => http.verify());

  const tick = () => new Promise((resolve) => setTimeout(resolve));

  async function open(status: string) {
    const fixture = TestBed.createComponent(VerifyAccountComponent);
    fixture.componentRef.setInput('id', '7');
    fixture.componentRef.setInput('hash', 'abc');
    fixture.componentRef.setInput('expires', '123');
    fixture.componentRef.setInput('signature', 'sig');
    fixture.detectChanges();

    // Precarga de reCAPTCHA (sin clave en pruebas)
    http.expectOne(`${environment.apiUrl}/config`).flush({ recaptcha_site_key: null });
    http.expectOne(statusUrl).flush({ status, message: 'Mensaje', email: 'a**@luna.test' });
    await tick();
    fixture.detectChanges();
    return fixture;
  }

  it('sends an already active account to the login with a notice', async () => {
    await open('already_verified');

    expect(navigate).toHaveBeenCalledWith(['/login'], { queryParams: { verified: 'active' } });
  });

  it('tells the user a new link was sent when this one expired', async () => {
    const fixture = await open('expired');

    const text = fixture.nativeElement.textContent as string;
    expect(text).toContain('Este enlace venció');
    expect(text).toContain('a**@luna.test');
    expect(navigate).not.toHaveBeenCalled();
  });

  it('confirms the account with the button and goes to the login', async () => {
    const fixture = await open('pending');

    (fixture.nativeElement.querySelector('button.btn-primary') as HTMLButtonElement).click();
    await tick();
    http.expectOne(environment.csrfCookieUrl).flush('');
    await tick();
    const request = http.expectOne(statusUrl);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ recaptcha_token: '' });
    request.flush({ status: 'verified', message: 'ok', email: null });
    await tick();

    expect(navigate).toHaveBeenCalledWith(['/login'], { queryParams: { verified: '1' } });
  });
});
