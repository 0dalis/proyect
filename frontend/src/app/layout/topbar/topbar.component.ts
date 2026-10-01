import { Component, computed, ElementRef, HostListener, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { RatingService } from '../../core/services/rating.service';
import { ToastService } from '../../core/services/toast.service';
import { initialsOf } from '../../shared/components/image-upload/image-upload.component';
import { ThemeToggleComponent } from '../../shared/components/theme-toggle/theme-toggle.component';
import { LayoutService } from '../layout.service';
import { NotificationBellComponent } from '../notification-bell/notification-bell.component';

/**
 * Barra superior fija: ubicación, tema, campana de notificaciones y menú del usuario.
 */
@Component({
  selector: 'app-topbar',
  imports: [RouterLink, ThemeToggleComponent, NotificationBellComponent],
  templateUrl: './topbar.component.html',
  styleUrl: './topbar.component.scss',
})
export class TopbarComponent {
  protected readonly auth = inject(AuthService);
  protected readonly layout = inject(LayoutService);
  private readonly ratings = inject(RatingService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly host = inject(ElementRef<HTMLElement>);

  protected readonly menuOpen = signal(false);

  protected readonly initials = computed(() => initialsOf(this.auth.user()?.name ?? ''));

  /** Cierra el menú del usuario al hacer clic fuera. */
  @HostListener('document:click', ['$event'])
  protected onDocumentClick(event: MouseEvent): void {
    if (!this.host.nativeElement.contains(event.target as Node)) {
      this.menuOpen.set(false);
    }
  }

  protected rate(): void {
    this.menuOpen.set(false);
    this.ratings.modalOpen.set(true);
  }

  protected async logout(): Promise<void> {
    this.menuOpen.set(false);
    await this.auth.logout();
    this.toast.info('Cerraste sesión correctamente.', {
      title: 'Hasta pronto',
      icon: 'box-arrow-right',
    });
    await this.router.navigate(['/login']);
  }
}
