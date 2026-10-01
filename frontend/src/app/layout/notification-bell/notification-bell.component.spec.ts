import { signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { PanelNotification } from '../../core/models';
import { NotificationService } from '../../core/services/notification.service';
import { NotificationBellComponent } from './notification-bell.component';

describe('NotificationBellComponent', () => {
  const item: PanelNotification = {
    id: 7,
    type: 'request_submitted',
    title: 'Luis Vega pidió permiso',
    body: '30 de septiembre · Cita médica',
    link: '/panel/solicitudes',
    read_at: null,
    created_at: new Date().toISOString(),
  };

  function setup(unread: number, latest: PanelNotification[]) {
    const service = {
      unread: signal(unread),
      latest: signal(latest),
      refreshLatest: vi.fn().mockResolvedValue(undefined),
      startPolling: vi.fn(),
      stopPolling: vi.fn(),
      markRead: vi.fn().mockResolvedValue(undefined),
      markAllRead: vi.fn().mockResolvedValue(undefined),
    };
    TestBed.configureTestingModule({
      imports: [NotificationBellComponent],
      providers: [provideRouter([]), { provide: NotificationService, useValue: service }],
    });
    const fixture = TestBed.createComponent(NotificationBellComponent);
    fixture.detectChanges();
    return { fixture, el: fixture.nativeElement as HTMLElement, service };
  }

  it('shows the unread count and starts polling', () => {
    const { el, service } = setup(3, [item]);

    expect(el.querySelector('.count')?.textContent?.trim()).toBe('3');
    expect(el.querySelector('.bell-button')?.getAttribute('aria-label')).toBe('Notificaciones, 3 sin leer');
    expect(service.startPolling).toHaveBeenCalled();
  });

  it('hides the badge when everything is read', () => {
    const { el } = setup(0, []);

    expect(el.querySelector('.count')).toBeNull();
  });

  it('opens the list and goes to the notification link', async () => {
    const { fixture, el, service } = setup(1, [item]);
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigateByUrl').mockResolvedValue(true);

    (el.querySelector('.bell-button') as HTMLButtonElement).click();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(el.textContent).toContain('Luis Vega pidió permiso');
    (el.querySelector('.item') as HTMLButtonElement).click();
    await fixture.whenStable();

    expect(service.markRead).toHaveBeenCalledWith(item);
    expect(navigate).toHaveBeenCalledWith('/panel/solicitudes');
  });
});
