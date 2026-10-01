import { Component, computed, inject, signal } from '@angular/core';
import { Toast, TOAST_POSITIONS, ToastService } from '../../../core/services/toast.service';

/**
 * Pinta las notificaciones de ToastService en sus ocho posiciones.
 * Se coloca una sola vez en AppComponent.
 *
 * La más reciente queda arriba. Al entrar se deslizan desde el borde más
 * cercano; al salir (tiempo cumplido, cierre o exceso de 5) regresan hacia
 * ese borde desvaneciéndose y las demás suben suavemente.
 */
@Component({
  selector: 'app-toast-container',
  templateUrl: './toast-container.component.html',
  styleUrl: './toast-container.component.scss',
})
export class ToastContainerComponent {
  protected readonly toastService = inject(ToastService);

  /** Toasts en modo icono que el usuario abrió para leer. */
  protected readonly expanded = signal<Set<number>>(new Set());

  protected readonly stacks = computed(() =>
    TOAST_POSITIONS.map((position) => ({
      position,
      // El servicio las guarda de la más vieja a la más nueva: se invierte
      toasts: this.toastService
        .toasts()
        .filter((t) => t.position === position)
        .reverse(),
    })),
  );

  protected isExpanded(toast: Toast): boolean {
    return this.expanded().has(toast.id);
  }

  /** En modo icono, tocarlo muestra el texto y pausa el tiempo. */
  protected toggle(toast: Toast): void {
    if (toast.display === 'full') {
      return;
    }
    const next = new Set(this.expanded());
    if (next.has(toast.id)) {
      next.delete(toast.id);
      this.toastService.resume(toast.id);
    } else {
      next.add(toast.id);
      this.toastService.pause(toast.id);
    }
    this.expanded.set(next);
  }

  protected close(toast: Toast, event?: Event): void {
    event?.stopPropagation();
    this.toastService.dismiss(toast.id);
  }
}
