import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import { fakeUser } from '../testing/fake-user';
import { AuthService } from './auth.service';
import { IdleService } from './idle.service';
import { ToastService } from './toast.service';

const MINUTE = 60_000;

describe('IdleService', () => {
  let auth: AuthService;
  let http: HttpTestingController;
  let toast: ToastService;
  let navigate: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.useFakeTimers();
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    });
    auth = TestBed.inject(AuthService);
    http = TestBed.inject(HttpTestingController);
    toast = TestBed.inject(ToastService);
    navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
    TestBed.inject(IdleService);
  });

  afterEach(() => {
    auth.clear();
    TestBed.tick();
    toast.clear();
    http.verify();
    vi.useRealTimers();
  });

  const signIn = () => {
    auth.user.set(fakeUser());
    TestBed.tick();
  };

  const idleFor = (minutes: number) => vi.advanceTimersByTime(minutes * MINUTE);

  const press = () => document.dispatchEvent(new KeyboardEvent('keydown'));

  const flushLogout = async () => {
    await vi.advanceTimersByTimeAsync(0);
    http.expectOne(`${environment.apiUrl}/auth/logout`).flush({});
    await vi.advanceTimersByTimeAsync(0);
  };

  it('warns five minutes before closing the session', () => {
    signIn();

    idleFor(114);
    expect(toast.toasts()).toHaveLength(0);

    idleFor(1);
    expect(toast.toasts()[0].title).toBe('¿Sigues ahí?');
  });

  it('closes the session after two hours without activity and goes to the login', async () => {
    signIn();

    idleFor(120);
    await flushLogout();

    expect(auth.user()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/login']);
    expect(toast.toasts().at(0)?.title).toBe('Sesión cerrada');
  });

  it('keyboard activity restarts the countdown and keeps the Laravel session alive', async () => {
    signIn();

    idleFor(116);
    press();
    // Hubo actividad: avisa a Laravel y quita el aviso de cierre
    http.expectOne(`${environment.apiUrl}/session/ping`).flush({ idle_minutes: 120 });
    expect(toast.toasts()).toHaveLength(0);

    idleFor(119);
    expect(auth.user()).not.toBeNull();
    http.expectNone(`${environment.apiUrl}/auth/logout`);
  });

  it('pings Laravel at most once every five minutes', () => {
    signIn();

    idleFor(2);
    press();
    http.expectNone(`${environment.apiUrl}/session/ping`);

    idleFor(4);
    document.dispatchEvent(new MouseEvent('mousemove'));
    http.expectOne(`${environment.apiUrl}/session/ping`).flush({ idle_minutes: 120 });
  });

  it('does not bring an expired session back to life (computer was asleep)', async () => {
    signIn();

    // Los temporizadores no corrieron, pero el reloj sí avanzó
    vi.setSystemTime(Date.now() + 121 * MINUTE);
    press();
    await flushLogout();

    expect(auth.user()).toBeNull();
    http.expectNone(`${environment.apiUrl}/session/ping`);
  });

  it('activity in another tab keeps this one open', () => {
    signIn();

    idleFor(119);
    window.dispatchEvent(
      new StorageEvent('storage', { key: 'asistcontrol.last-activity', newValue: String(Date.now()) }),
    );
    idleFor(100);

    expect(auth.user()).not.toBeNull();
  });

  it('closes this tab when another tab expired the session', async () => {
    signIn();

    window.dispatchEvent(
      new StorageEvent('storage', { key: 'asistcontrol.idle-logout', newValue: String(Date.now()) }),
    );
    await vi.advanceTimersByTimeAsync(0);

    // La otra pestaña ya cerró la sesión en Laravel
    http.expectNone(`${environment.apiUrl}/auth/logout`);
    expect(auth.user()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/login']);
  });

  it('ignores activity while nobody is signed in', () => {
    press();
    idleFor(200);

    expect(toast.toasts()).toHaveLength(0);
    expect(navigate).not.toHaveBeenCalled();
  });
});
