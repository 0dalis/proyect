import { inject, Injectable, signal } from '@angular/core';
import { Paginated, PanelNotification } from '../models';
import { ApiService } from './api.service';

/** Cada cuánto se revisa si llegó algo nuevo a la campana. */
const POLL_MS = 60_000;

/**
 * Campana del menú superior:
 * - Dueño, administradores y gerentes: solicitudes que les toca revisar.
 * - Quien pidió algo: si se aprobó o rechazó y por qué.
 * - Gerentes y empleados: avisos de la empresa.
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly api = inject(ApiService);

  readonly unread = signal(0);
  /** Las más recientes, para el menú de la campana. */
  readonly latest = signal<PanelNotification[]>([]);

  private timer: ReturnType<typeof setInterval> | undefined;

  async list(params: { unread?: boolean; page?: number; per_page?: number } = {}): Promise<
    Paginated<PanelNotification> & { unread: number }
  > {
    const response = await this.api.get<Paginated<PanelNotification> & { unread: number }>(
      'panel-notifications',
      { unread: params.unread ? 1 : null, page: params.page, per_page: params.per_page },
    );
    this.unread.set(response.unread);
    return response;
  }

  async refreshLatest(): Promise<void> {
    const response = await this.list({ per_page: 8 });
    this.latest.set(response.data);
  }

  async markRead(notification: PanelNotification): Promise<void> {
    if (notification.read_at) {
      return;
    }
    const response = await this.api.post<{ unread: number }>(
      `panel-notifications/${notification.id}/read`,
    );
    this.unread.set(response.unread);
    this.markLocally((item) => item.id === notification.id);
  }

  async markAllRead(): Promise<void> {
    await this.api.post('panel-notifications/read-all');
    this.unread.set(0);
    this.markLocally(() => true);
  }

  /** Revisa el contador cada minuto mientras el panel está abierto. */
  startPolling(): void {
    this.stopPolling();
    this.poll();
    this.timer = setInterval(() => this.poll(), POLL_MS);
  }

  stopPolling(): void {
    clearInterval(this.timer);
    this.timer = undefined;
  }

  private async poll(): Promise<void> {
    if (document.visibilityState === 'hidden') {
      return;
    }
    try {
      const { unread } = await this.api.get<{ unread: number }>('panel-notifications/unread');
      if (unread !== this.unread()) {
        await this.refreshLatest();
      }
    } catch {
      // Sin conexión: se intenta en el siguiente ciclo
    }
  }

  private markLocally(match: (item: PanelNotification) => boolean): void {
    const now = new Date().toISOString();
    this.latest.update((items) =>
      items.map((item) => (match(item) && !item.read_at ? { ...item, read_at: now } : item)),
    );
  }
}

/** Icono y color de cada tipo de notificación. */
export const NOTIFICATION_STYLE: Record<
  PanelNotification['type'],
  { icon: string; tone: string; label: string }
> = {
  request_submitted: { icon: 'inbox', tone: 'primary', label: 'Por revisar' },
  request_approved: { icon: 'check-circle', tone: 'success', label: 'Aprobada' },
  request_rejected: { icon: 'x-circle', tone: 'danger', label: 'Rechazada' },
  announcement: { icon: 'megaphone', tone: 'info', label: 'Aviso' },
};
