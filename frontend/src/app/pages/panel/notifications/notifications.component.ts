import { DatePipe } from '@angular/common';
import { Component, computed, inject, input, OnInit, signal } from '@angular/core';
import { Router } from '@angular/router';
import { InboxItem, PanelNotification } from '../../../core/models';
import { AnnouncementService } from '../../../core/services/announcement.service';
import { AuthService } from '../../../core/services/auth.service';
import { NOTIFICATION_STYLE, NotificationService } from '../../../core/services/notification.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

type Tab = 'historial' | 'avisos' | 'noticias';

/**
 * Historial de la campana (solicitudes por revisar, decisiones y avisos) y,
 * para quien es empleado, los avisos y las noticias de la empresa.
 */
@Component({
  selector: 'app-notifications',
  imports: [SkeletonComponent, DatePipe, PageHeaderComponent],
  templateUrl: './notifications.component.html',
  styleUrl: './notifications.component.scss',
})
export class NotificationsComponent implements OnInit {
  protected readonly auth = inject(AuthService);
  protected readonly notifications = inject(NotificationService);
  private readonly announcementService = inject(AnnouncementService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);

  /** ?tab=avisos desde la campana. */
  readonly tabParam = input<string | undefined>(undefined, { alias: 'tab' });

  protected readonly loading = signal(true);
  protected readonly tab = signal<Tab>('historial');
  protected readonly onlyUnread = signal(false);
  protected readonly history = signal<PanelNotification[]>([]);
  protected readonly page = signal(1);
  protected readonly lastPage = signal(1);
  protected readonly inbox = signal<InboxItem[]>([]);
  protected readonly news = signal<InboxItem[]>([]);
  protected readonly style = NOTIFICATION_STYLE;

  /** Avisos y noticias son para quien está ligado a un empleado. */
  protected readonly isEmployee = computed(() => !!this.auth.user()?.employee);

  async ngOnInit(): Promise<void> {
    const requested = this.tabParam();
    if ((requested === 'avisos' || requested === 'noticias') && this.isEmployee()) {
      this.tab.set(requested);
    }
    try {
      await Promise.all([this.loadHistory(), this.loadAnnouncements()]);
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected async setFilter(onlyUnread: boolean): Promise<void> {
    this.onlyUnread.set(onlyUnread);
    await this.loadHistory().catch((error) => this.toast.error(errorMessage(error)));
  }

  protected async loadMore(): Promise<void> {
    await this.loadHistory(this.page() + 1).catch((error) => this.toast.error(errorMessage(error)));
  }

  protected async open(item: PanelNotification): Promise<void> {
    await this.notifications.markRead(item).catch(() => undefined);
    this.markHistoryRead((i) => i.id === item.id);
    if (item.link) {
      await this.router.navigateByUrl(item.link);
    }
  }

  protected async markAll(): Promise<void> {
    await this.notifications.markAllRead();
    this.markHistoryRead(() => true);
  }

  protected async markAnnouncementRead(item: InboxItem): Promise<void> {
    await this.announcementService.markRead(item.id);
    this.inbox.update((items) =>
      items.map((i) => (i.id === item.id ? { ...i, pivot: { read_at: new Date().toISOString() } } : i)),
    );
  }

  private async loadHistory(page = 1): Promise<void> {
    const response = await this.notifications.list({ unread: this.onlyUnread(), page, per_page: 20 });
    this.history.update((items) => (page === 1 ? response.data : [...items, ...response.data]));
    this.page.set(response.current_page);
    this.lastPage.set(response.last_page);
  }

  private async loadAnnouncements(): Promise<void> {
    if (!this.isEmployee()) {
      return;
    }
    const [inbox, news] = await Promise.all([
      this.announcementService.inbox(),
      this.announcementService.news(),
    ]);
    this.inbox.set(inbox);
    this.news.set(news);
  }

  private markHistoryRead(match: (item: PanelNotification) => boolean): void {
    const now = new Date().toISOString();
    this.history.update((items) =>
      items.map((item) => (match(item) && !item.read_at ? { ...item, read_at: now } : item)),
    );
  }
}
