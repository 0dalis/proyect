import { Injectable, signal } from '@angular/core';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

/** Ocho posiciones; el centro de la pantalla no se permite. */
export type ToastPosition =
  | 'top-start'
  | 'top-center'
  | 'top-end'
  | 'middle-start'
  | 'middle-end'
  | 'bottom-start'
  | 'bottom-center'
  | 'bottom-end';

/**
 * auto: caja completa en pantallas grandes, solo el icono en pantallas chicas.
 * full: siempre la caja completa.
 * icon: siempre solo el icono (se expande al tocarlo).
 */
export type ToastDisplay = 'auto' | 'full' | 'icon';

export interface ToastOptions {
  title?: string;
  /** Milisegundos visible; 0 = hasta que el usuario la cierre. */
  duration?: number;
  position?: ToastPosition;
  display?: ToastDisplay;
  /** Icono de Bootstrap Icons (sin "bi-") para reemplazar el del tipo. */
  icon?: string;
  closable?: boolean;
}

export interface Toast {
  id: number;
  type: ToastType;
  title: string;
  message: string;
  icon: string;
  duration: number;
  position: ToastPosition;
  display: ToastDisplay;
  closable: boolean;
  /** Tiempo restante; baja solo cuando no está en pausa. */
  remaining: number;
  paused: boolean;
  startedAt: number;
}

const DEFAULT_TITLES: Record<ToastType, string> = {
  success: 'Listo',
  error: 'Algo salió mal',
  warning: 'Atención',
  info: 'Aviso',
};

const DEFAULT_ICONS: Record<ToastType, string> = {
  success: 'check-circle-fill',
  error: 'x-octagon-fill',
  warning: 'exclamation-triangle-fill',
  info: 'info-circle-fill',
};

export const TOAST_POSITIONS: ToastPosition[] = [
  'top-start',
  'top-center',
  'top-end',
  'middle-start',
  'middle-end',
  'bottom-start',
  'bottom-center',
  'bottom-end',
];

/** Máximo apiladas por posición; al llegar una nueva, la más antigua sale. */
const MAX_PER_POSITION = 5;

/**
 * Notificaciones tipo "toast". Se muestran con <app-toast-container>, que
 * vive una sola vez en AppComponent.
 *
 *   toast.success('Empleado guardado');
 *   toast.error(errorMessage(error), { position: 'bottom-center', display: 'full' });
 */
@Injectable({ providedIn: 'root' })
export class ToastService {
  private readonly items = signal<Toast[]>([]);
  private readonly timers = new Map<number, ReturnType<typeof setTimeout>>();
  private nextId = 1;

  readonly toasts = this.items.asReadonly();

  /** Valores por omisión de toda la aplicación. */
  defaults: Required<Pick<ToastOptions, 'duration' | 'position' | 'display' | 'closable'>> = {
    duration: 4500,
    position: 'top-end',
    display: 'auto',
    closable: true,
  };

  success(message: string, options?: ToastOptions): number {
    return this.show('success', message, options);
  }

  error(message: string, options?: ToastOptions): number {
    return this.show('error', message, { duration: 6500, ...options });
  }

  warning(message: string, options?: ToastOptions): number {
    return this.show('warning', message, options);
  }

  info(message: string, options?: ToastOptions): number {
    return this.show('info', message, options);
  }

  show(type: ToastType, message: string, options: ToastOptions = {}): number {
    const position = options.position ?? this.defaults.position;
    const duration = options.duration ?? this.defaults.duration;
    const toast: Toast = {
      id: this.nextId++,
      type,
      title: options.title ?? DEFAULT_TITLES[type],
      message,
      icon: options.icon ?? DEFAULT_ICONS[type],
      duration,
      position,
      display: options.display ?? this.defaults.display,
      closable: options.closable ?? this.defaults.closable,
      remaining: duration,
      paused: false,
      startedAt: Date.now(),
    };

    this.items.update((list) => {
      const samePosition = list.filter((t) => t.position === position);
      const overflow = samePosition.length - MAX_PER_POSITION + 1;
      const dropped = overflow > 0 ? samePosition.slice(0, overflow).map((t) => t.id) : [];
      dropped.forEach((id) => this.clearTimer(id));
      return [...list.filter((t) => !dropped.includes(t.id)), toast];
    });

    this.schedule(toast.id, duration);
    return toast.id;
  }

  dismiss(id: number): void {
    this.clearTimer(id);
    this.items.update((list) => list.filter((t) => t.id !== id));
  }

  clear(): void {
    this.timers.forEach((timer) => clearTimeout(timer));
    this.timers.clear();
    this.items.set([]);
  }

  /** Detiene la cuenta regresiva (cursor encima o toast expandido). */
  pause(id: number): void {
    const toast = this.items().find((t) => t.id === id);
    if (!toast || toast.paused || toast.duration === 0) {
      return;
    }
    this.clearTimer(id);
    const remaining = Math.max(0, toast.remaining - (Date.now() - toast.startedAt));
    this.patch(id, { paused: true, remaining });
  }

  resume(id: number): void {
    const toast = this.items().find((t) => t.id === id);
    if (!toast || !toast.paused) {
      return;
    }
    this.patch(id, { paused: false, startedAt: Date.now() });
    this.schedule(id, toast.remaining);
  }

  byPosition(position: ToastPosition): Toast[] {
    return this.items().filter((t) => t.position === position);
  }

  private schedule(id: number, delay: number): void {
    if (delay > 0) {
      this.timers.set(
        id,
        setTimeout(() => this.dismiss(id), delay),
      );
    }
  }

  private clearTimer(id: number): void {
    clearTimeout(this.timers.get(id));
    this.timers.delete(id);
  }

  private patch(id: number, changes: Partial<Toast>): void {
    this.items.update((list) => list.map((t) => (t.id === id ? { ...t, ...changes } : t)));
  }
}
