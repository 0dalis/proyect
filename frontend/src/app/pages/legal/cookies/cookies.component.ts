import { Component, inject } from '@angular/core';
import { CookieConsentService } from '../../../core/services/cookie-consent.service';
import { LEGAL } from '../../../shared/constants/legal';

@Component({
  selector: 'app-cookies',
  templateUrl: './cookies.component.html',
  styleUrl: './cookies.component.scss',
})
export class CookiesComponent {
  protected readonly legal = LEGAL;
  protected readonly consent = inject(CookieConsentService);
}
