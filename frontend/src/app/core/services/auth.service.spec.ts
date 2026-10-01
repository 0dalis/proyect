import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { fakeUser } from '../testing/fake-user';
import { AuthService } from './auth.service';

describe('AuthService', () => {
  let auth: AuthService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    auth = TestBed.inject(AuthService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  /** Deja que las promesas encadenadas lleguen a la siguiente petición. */
  const tick = () => new Promise((resolve) => setTimeout(resolve));

  it('asks for the CSRF cookie before logging in and never stores a token', async () => {
    const login = auth.login('owner@demo.test', 'password');

    // Sin clave de reCAPTCHA (desarrollo) el token va vacío
    http.expectOne(`${environment.apiUrl}/config`).flush({ recaptcha_site_key: null });
    await tick();
    http.expectOne(environment.csrfCookieUrl).flush('');
    await tick();
    const request = http.expectOne(`${environment.apiUrl}/auth/login`);
    expect(request.request.body.client).toBe('web');
    expect(request.request.body.recaptcha_token).toBe('');
    request.flush({ user: fakeUser() });
    await tick();
    http.expectOne(`${environment.apiUrl}/me`).flush(fakeUser());

    expect((await login).role).toBe('owner');
    expect(localStorage.getItem('asist.token')).toBeNull();
  });

  it('loads the user from /me', async () => {
    const loading = auth.loadUser();
    http.expectOne(`${environment.apiUrl}/me`).flush(fakeUser());

    expect((await loading)?.role).toBe('owner');
    expect(auth.can('reports.view')).toBe(true);
  });

  // Regresión: /me respondía { data: {...} } y el panel quedaba en blanco
  it('accepts a user wrapped in "data"', async () => {
    const loading = auth.loadUser();
    http.expectOne(`${environment.apiUrl}/me`).flush({ data: fakeUser() });

    expect((await loading)?.company.name).toBe('Almacenes Demo');
    expect(auth.user()?.permissions.length).toBeGreaterThan(0);
  });

  it('returns null without a session and does not ask again', async () => {
    const first = auth.loadUser();
    http
      .expectOne(`${environment.apiUrl}/me`)
      .flush({ message: 'Unauthenticated.' }, { status: 401, statusText: 'Unauthorized' });

    expect(await first).toBeNull();
    expect(await auth.loadUser()).toBeNull();
    http.expectNone(`${environment.apiUrl}/me`);
  });

  it('drops the session when /me returns something unusable', async () => {
    const loading = auth.loadUser();
    http.expectOne(`${environment.apiUrl}/me`).flush({ unexpected: true });

    expect(await loading).toBeNull();
    expect(auth.isAuthenticated()).toBe(false);
  });

  it('reports modules and roles from the current user', () => {
    auth.user.set(fakeUser({ role: 'admin' }));

    expect(auth.hasRole('owner', 'admin')).toBe(true);
    expect(auth.moduleEnabled('payroll')).toBe(true);
    expect(auth.moduleEnabled('bonuses')).toBe(false);
  });
});
