import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';
import { AuthService } from '../services/auth.service';
import { ToastService } from '../services/toast.service';
import { fakeUser } from '../testing/fake-user';
import { authInterceptor } from './auth.interceptor';
import { sessionInterceptor } from './session.interceptor';

describe('session and auth interceptors', () => {
  let http: HttpClient;
  let controller: HttpTestingController;
  let auth: AuthService;
  let router: Router;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideRouter([]),
        provideHttpClient(withInterceptors([authInterceptor, sessionInterceptor])),
        provideHttpClientTesting(),
      ],
    });
    http = TestBed.inject(HttpClient);
    controller = TestBed.inject(HttpTestingController);
    auth = TestBed.inject(AuthService);
    auth.user.set(fakeUser());
    router = TestBed.inject(Router);
    vi.spyOn(router, 'navigate').mockResolvedValue(true);
  });

  it('sends cookies and marks the call as web, without an Authorization header', () => {
    firstValueFrom(http.get(`${environment.apiUrl}/me`)).catch(() => undefined);
    const request = controller.expectOne(`${environment.apiUrl}/me`);

    expect(request.request.withCredentials).toBe(true);
    expect(request.request.headers.get('X-Client')).toBe('web');
    expect(request.request.headers.has('Authorization')).toBe(false);
    request.flush({});
  });

  it('logs out and redirects when the session expired', async () => {
    const call = firstValueFrom(http.get(`${environment.apiUrl}/employees`));
    controller
      .expectOne(`${environment.apiUrl}/employees`)
      .flush({ message: 'Unauthenticated.' }, { status: 401, statusText: 'Unauthorized' });

    await expect(call).rejects.toBeTruthy();
    expect(auth.user()).toBeNull();
    expect(router.navigate).toHaveBeenCalledWith(['/login']);
    expect(TestBed.inject(ToastService).toasts()[0].message).toContain('expiró');
  });

  it('renews the CSRF token once when Laravel answers 419', async () => {
    const call = firstValueFrom(http.post(`${environment.apiUrl}/requests`, {}));
    controller
      .expectOne(`${environment.apiUrl}/requests`)
      .flush({ message: 'CSRF token mismatch.' }, { status: 419, statusText: 'Page Expired' });

    await new Promise((resolve) => setTimeout(resolve));
    controller.expectOne(environment.csrfCookieUrl).flush('');
    await new Promise((resolve) => setTimeout(resolve));
    controller.expectOne(`${environment.apiUrl}/requests`).flush({ id: 1 });

    expect(await call).toEqual({ id: 1 });
  });

  it('keeps the session on an ordinary 403 (permission)', async () => {
    const call = firstValueFrom(http.get(`${environment.apiUrl}/users`));
    controller
      .expectOne(`${environment.apiUrl}/users`)
      .flush({ message: 'Forbidden' }, { status: 403, statusText: 'Forbidden' });

    await expect(call).rejects.toBeTruthy();
    expect(auth.user()).not.toBeNull();
  });
});
