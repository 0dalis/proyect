import { DestroyRef, effect, inject, Injectable } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiService } from './api.service';
import { AuthService } from './auth.service';
import { ToastService } from './toast.service';

/** Eventos que cuentan como "el usuario sigue aquí". */
const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'wheel', 'touchstart', 'scroll'] as const;

/** Claves compartidas entre pestañas del mismo navegador. */
const LAST_ACTIVITY_KEY = 'asistcontrol.last-activity';
const LOGOUT_KEY = 'asistcontrol.idle-logout';

/** Cada cuánto se revisa si ya venció el plazo. */
const CHECK_EVERY_MS = 15_000;
/** Una ráfaga de eventos (mover el mouse) se registra como una sola actividad. */
const THROTTLE_MS = 1_000;

const MINUTE = 60_000;

/**
 * Cierra la sesión tras `environment.session.idleMinutes` (2 h) sin actividad.
 *
 * - Actividad: mouse, teclado, scroll, toques y navegar entre vistas.
 * - Mientras hay actividad, avisa a Laravel cada `pingMinutes` para que la
 *   cookie de sesión no venza aunque el usuario no haga peticiones.
 * - Avisa `warnMinutes` antes de cerrar la sesión.
 * - Las pestañas abiertas comparten la última actividad: usar una mantiene
 *   viva la sesión en todas, y al vencer se cierran todas.
 *
 * Se activa solo al haber usuario (AuthService.user) y se detiene al salir.
 */
@Injectable({ providedIn: 'root' })
export class IdleService {
  private readonly auth = inject(AuthService);
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  private readonly idleMs = environment.session.idleMinutes * MINUTE;
  private readonly warnMs = environment.session.warnMinutes * MINUTE;
  private readonly pingMs = environment.session.pingMinutes * MINUTE;

  private running = false;
  private lastActivity = 0;
  private lastPing = 0;
  private checkTimer: ReturnType<typeof setInterval> | null = null;
  private warningId: number | null = null;

  private readonly onActivity = () => this.recordActivity();
  private readonly onVisibility = () => {
    if (document.visibilityState === 'visible') {
      this.check();
    }
  };
  private readonly onStorage = (event: StorageEvent) => this.syncFromOtherTab(event);

  constructor() {
    effect(() => (this.auth.user() ? this.start() : this.stop()));

    this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe(() => this.recordActivity());

    inject(DestroyRef).onDestroy(() => this.stop());
  }

  /** Minutos que faltan para el cierre por inactividad. */
  remainingMinutes(): number {
    return Math.max(0, Math.ceil((this.lastActivity + this.idleMs - Date.now()) / MINUTE));
  }

  private start(): void {
    if (this.running) {
      return;
    }
    this.running = true;

    // Acaba de iniciar sesión o el servidor confirmó la sesión con /me
    this.lastActivity = Date.now();
    this.lastPing = this.lastActivity;
    this.store(LAST_ACTIVITY_KEY, this.lastActivity);

    for (const name of ACTIVITY_EVENTS) {
      document.addEventListener(name, this.onActivity, { passive: true, capture: true });
    }
    document.addEventListener('visibilitychange', this.onVisibility);
    window.addEventListener('storage', this.onStorage);
    this.checkTimer = setInterval(() => this.check(), CHECK_EVERY_MS);
  }

  private stop(): void {
    if (!this.running) {
      return;
    }
    this.running = false;

    for (const name of ACTIVITY_EVENTS) {
      document.removeEventListener(name, this.onActivity, { capture: true });
    }
    document.removeEventListener('visibilitychange', this.onVisibility);
    window.removeEventListener('storage', this.onStorage);
    if (this.checkTimer) {
      clearInterval(this.checkTimer);
      this.checkTimer = null;
    }
    this.hideWarning();
  }

  private recordActivity(): void {
    if (!this.running) {
      return;
    }
    const now = Date.now();
    if (now - this.lastActivity < THROTTLE_MS) {
      return;
    }
    // El equipo pudo estar suspendido: si el plazo ya venció, no se revive.
    if (now - this.lastActivity >= this.idleMs) {
      this.check();
      return;
    }

    this.lastActivity = now;
    this.store(LAST_ACTIVITY_KEY, now);
    this.hideWarning();

    if (now - this.lastPing >= this.pingMs) {
      this.lastPing = now;
      // Si la sesión ya venció en Laravel, el interceptor cierra la sesión
      this.api.get('session/ping').catch(() => undefined);
    }
  }

  private check(): void {
    if (!this.running) {
      return;
    }
    const idle = Date.now() - this.lastActivity;

    if (idle >= this.idleMs) {
      void this.expire(true);
    } else if (idle >= this.idleMs - this.warnMs) {
      this.showWarning();
    }
  }

  private showWarning(): void {
    if (this.warningId !== null) {
      return;
    }
    const minutes = this.remainingMinutes();
    this.warningId = this.toast.warning(
      `Tu sesión se cerrará en ${minutes} ${minutes === 1 ? 'minuto' : 'minutos'} por inactividad. Mueve el mouse o presiona una tecla para continuar.`,
      { title: '¿Sigues ahí?', icon: 'hourglass-split', duration: 0 },
    );
  }

  private hideWarning(): void {
    if (this.warningId !== null) {
      this.toast.dismiss(this.warningId);
      this.warningId = null;
    }
  }

  /**
   * Cierra la sesión en Laravel y en Angular y lleva al login.
   * `notifyTabs` avisa a las demás pestañas para que hagan lo mismo.
   */
  private async expire(notifyTabs: boolean): Promise<void> {
    this.stop();
    if (notifyTabs) {
      this.store(LOGOUT_KEY, Date.now());
      // Si la cookie ya venció, Laravel responde 401 y basta con limpiar aquí
      await this.auth.logout().catch(() => undefined);
    } else {
      this.auth.clear();
    }

    this.toast.warning(
      `Tu sesión se cerró tras ${this.idleLabel()} sin actividad. Vuelve a iniciar sesión.`,
      { title: 'Sesión cerrada', icon: 'lock', duration: 0 },
    );
    await this.router.navigate(['/login']);
  }

  private syncFromOtherTab(event: StorageEvent): void {
    if (!this.running || !event.newValue) {
      return;
    }
    if (event.key === LAST_ACTIVITY_KEY) {
      const at = Number(event.newValue);
      if (at > this.lastActivity) {
        this.lastActivity = at;
        this.hideWarning();
      }
    } else if (event.key === LOGOUT_KEY) {
      void this.expire(false);
    }
  }

  private idleLabel(): string {
    const minutes = environment.session.idleMinutes;
    if (minutes % 60 === 0) {
      const hours = minutes / 60;
      return `${hours} ${hours === 1 ? 'hora' : 'horas'}`;
    }
    return `${minutes} minutos`;
  }

  /** localStorage puede no estar disponible (modo privado, bloqueado). */
  private store(key: string, value: number): void {
    try {
      localStorage.setItem(key, String(value));
    } catch {
      // Sin sincronización entre pestañas; esta pestaña sigue funcionando
    }
  }
}
