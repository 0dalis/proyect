import { Component, ElementRef, HostListener, inject, OnDestroy, OnInit, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { PanelNotification } from '../../core/models';
import { NOTIFICATION_STYLE, NotificationService } from '../../core/services/notification.service';
import { timeAgo } from '../../shared/utils/time-ago';

/**
 * Campana del menú superior con las notificaciones más recientes. Al abrir
 * una se marca leída y lleva a su pantalla (p. ej. Solicitudes).
 */
@Component({
  selector: 'app-notification-bell',
  imports: [RouterLink],
  templateUrl: './notification-bell.component.html',
  styleUrl: './notification-bell.component.scss',
})
export class NotificationBellComponent implements OnInit, OnDestroy {
  protected readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);
  private readonly host = inject(ElementRef<HTMLElement>);

  protected readonly open = signal(false);
  protected readonly loading = signal(false);
  protected readonly style = NOTIFICATION_STYLE;
  protected readonly timeAgo = timeAgo;

  ngOnInit(): void {
    this.notifications.refreshLatest().catch(() => undefined);
    this.notifications.startPolling();
  }

  ngOnDestroy(): void {
    this.notifications.stopPolling();
  }

  @HostListener('document:click', ['$event'])
  protected onDocumentClick(event: MouseEvent): void {
    if (!this.host.nativeElement.contains(event.target as Node)) {
      this.open.set(false);
    }
  }

  @HostListener('document:keydown.escape')
  protected close(): void {
    this.open.set(false);
  }

  protected async toggle(): Promise<void> {
    this.open.update((open) => !open);
    if (this.open()) {
      this.loading.set(true);
      await this.notifications.refreshLatest().catch(() => undefined);
      this.loading.set(false);
    }
  }

  protected async select(item: PanelNotification): Promise<void> {
    this.open.set(false);
    await this.notifications.markRead(item).catch(() => undefined);
    if (item.link) {
      await this.router.navigateByUrl(item.link);
    }
  }

  protected async markAll(): Promise<void> {
    await this.notifications.markAllRead().catch(() => undefined);
  }
}
