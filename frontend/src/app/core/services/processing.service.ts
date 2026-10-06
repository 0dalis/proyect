import { Injectable } from '@angular/core';
import Swal from 'sweetalert2';

/** Si la acción termina antes, no se muestra nada (evita parpadeos). */
const SHOW_AFTER_MS = 250;
/** Ya visible, se queda al menos este tiempo para que se alcance a leer. */
const MIN_VISIBLE_MS = 500;

const POPUP_CLASS = 'app-swal--processing';

/**
 * Alerta "Procesando…" mientras una acción del usuario espera al servidor
 * (o genera un PDF): la pantalla no se queda quieta y el modal bloquea los
 * clics repetidos. Varias acciones a la vez comparten la misma alerta.
 *
 * Las peticiones que guardan, eliminan o descargan la activan solas
 * (processingInterceptor); lo demás se envuelve con run().
 */
@Injectable({ providedIn: 'root' })
export class ProcessingService {
  private pending = 0;
  private shownAt: number | null = null;
  private showTimer: ReturnType<typeof setTimeout> | null = null;
  private hideTimer: ReturnType<typeof setTimeout> | null = null;

  async run<T>(task: () => Promise<T>): Promise<T> {
    this.begin();
    try {
      return await task();
    } finally {
      this.end();
    }
  }

  begin(): void {
    this.pending++;
    this.clear('hide');
    if (this.pending === 1 && this.shownAt === null) {
      this.showTimer = setTimeout(() => this.show(), SHOW_AFTER_MS);
    }
  }

  end(): void {
    this.pending = Math.max(0, this.pending - 1);
    if (this.pending > 0) {
      return;
    }
    this.clear('show');
    if (this.shownAt !== null) {
      const wait = Math.max(0, MIN_VISIBLE_MS - (Date.now() - this.shownAt));
      this.hideTimer = setTimeout(() => this.hide(), wait);
    }
  }

  private show(): void {
    this.showTimer = null;
    // Si ya hay otro diálogo abierto (una confirmación), no se reemplaza
    if (Swal.isVisible()) {
      return;
    }
    this.shownAt = Date.now();
    void Swal.fire({
      title: 'Procesando…',
      text: 'Espera un momento, por favor.',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      customClass: {
        popup: `app-swal ${POPUP_CLASS}`,
        title: 'app-swal__title',
        htmlContainer: 'app-swal__text',
      },
      didOpen: () => Swal.showLoading(),
    });
  }

  private hide(): void {
    this.hideTimer = null;
    this.shownAt = null;
    // Solo cierra su propia alerta, nunca un diálogo que se abrió después
    if (Swal.getPopup()?.classList.contains(POPUP_CLASS)) {
      Swal.close();
    }
  }

  private clear(timer: 'show' | 'hide'): void {
    const key = timer === 'show' ? 'showTimer' : 'hideTimer';
    if (this[key]) {
      clearTimeout(this[key]);
      this[key] = null;
    }
  }
}
