import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CookieConsentService } from '../../../core/services/cookie-consent.service';

/**
 * Aviso de cookies. Hoy solo hay cookies necesarias; "Aceptar todas" queda
 * listo para cuando se agreguen cookies opcionales.
 */
@Component({
  selector: 'app-cookie-banner',
  imports: [RouterLink],
  templateUrl: './cookie-banner.component.html',
  styleUrl: './cookie-banner.component.scss',
})
export class CookieBannerComponent {
  protected readonly consent = inject(CookieConsentService);
}
