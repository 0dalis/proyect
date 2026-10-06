import { inject, Injectable, signal } from '@angular/core';
import { Announcement, InboxItem, Paginated } from '../models';
import { ApiService } from './api.service';
import { silent } from '../interceptors/processing.interceptor';

export interface NewAnnouncement {
  title: string;
  body: string;
  link_url: string | null;
  audience_type: 'all' | 'areas' | 'offices' | 'roles' | 'employees';
  audience_ids: (number | string)[] | null;
  show_on_kiosk: boolean;
  publish_at: string | null;
}

@Injectable({ providedIn: 'root' })
export class AnnouncementService {
  private readonly api = inject(ApiService);

  /** Avisos sin leer del usuario actual (para la campana del menú superior). */
  readonly unread = signal(0);

  sent(): Promise<Paginated<Announcement>> {
    return this.api.get<Paginated<Announcement>>('announcements');
  }

  send(payload: NewAnnouncement): Promise<Announcement> {
    return this.api.post<Announcement>('announcements', payload);
  }

  async inbox(): Promise<InboxItem[]> {
    const items = (await this.api.get<Paginated<InboxItem>>('notifications')).data;
    this.unread.set(items.filter((item) => !item.pivot?.read_at).length);
    return items;
  }

  async markRead(id: number): Promise<void> {
    // Se marca sola al abrirla: sin "Procesando…"
    await this.api.post(`notifications/${id}/read`, {}, undefined, silent());
    this.unread.update((count) => Math.max(0, count - 1));
  }

  news(): Promise<InboxItem[]> {
    return this.api.get<InboxItem[]>('news');
  }
}
