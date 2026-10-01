import { DatePipe } from '@angular/common';
import { Component, computed, inject } from '@angular/core';
import { RouterLink, RouterOutlet } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { FirstAccessModalComponent } from '../first-access/first-access-modal.component';
import { OnboardingModalComponent } from '../onboarding/onboarding-modal.component';
import { RatingModalComponent } from '../rating/rating-modal.component';
import { SidebarComponent } from '../sidebar/sidebar.component';
import { TopbarComponent } from '../topbar/topbar.component';

/**
 * Estructura del panel: menú lateral + menú superior + contenido de la ruta.
 * La primera vez del dueño solo se muestra el modal de bienvenida, y quien
 * entra con contraseña temporal solo ve el modal para cambiarla.
 */
@Component({
  selector: 'app-panel-layout',
  imports: [
    RouterOutlet,
    RouterLink,
    DatePipe,
    SidebarComponent,
    TopbarComponent,
    OnboardingModalComponent,
    FirstAccessModalComponent,
    RatingModalComponent,
  ],
  templateUrl: './panel-layout.component.html',
  styleUrl: './panel-layout.component.scss',
})
export class PanelLayoutComponent {
  protected readonly auth = inject(AuthService);

  protected readonly onboarding = computed(() => !!this.auth.user()?.company.onboarding);
  /** Entró con contraseña temporal: primero la cambia y crea su PIN. */
  protected readonly firstAccess = computed(() => !!this.auth.user()?.must_change_password);

  /** Cobro fallido: a los 3 días la empresa pasa a Free. */
  protected readonly pastDueDeadline = computed(() => {
    const since = this.auth.user()?.company.past_due_since;
    if (!since) {
      return null;
    }
    const deadline = new Date(since);
    deadline.setDate(deadline.getDate() + 3);
    return deadline;
  });
}
