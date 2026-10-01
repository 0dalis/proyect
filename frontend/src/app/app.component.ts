import { Component, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { IdleService } from './core/services/idle.service';
import { ThemeService } from './core/services/theme.service';
import { CookieBannerComponent } from './shared/components/cookie-banner/cookie-banner.component';
import { ToastContainerComponent } from './shared/components/toast-container/toast-container.component';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, ToastContainerComponent, CookieBannerComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss',
})
export class AppComponent {
  // Se inyecta aquí para que el tema siga al sistema desde el arranque
  private readonly theme = inject(ThemeService);
  // Cierra la sesión tras 2 horas sin actividad (se activa al iniciar sesión)
  private readonly idle = inject(IdleService);
}
