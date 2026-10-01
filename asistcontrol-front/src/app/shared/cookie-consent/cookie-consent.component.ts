import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

import { environment } from '../../../environments/environment';

const CONSENT_COOKIE = 'cookie_consent';
const COOKIE_DAYS = 365;

@Component({
  selector: 'app-cookie-consent',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './cookie-consent.component.html',
  styleUrl: './cookie-consent.component.css'
})
export class CookieConsentComponent {

  visible = false;
  policyUrl = environment.apiUrl.replace(/\/api\/?$/, '') + '/cookies';

  constructor() {
    this.visible = this.readConsent() === null;
  }

  accept(): void {
    const expires = new Date(Date.now() + COOKIE_DAYS * 864e5).toUTCString();
    document.cookie = `${CONSENT_COOKIE}=accepted; expires=${expires}; path=/; SameSite=Lax`;
    this.visible = false;
  }

  private readConsent(): string | null {
    const match = document.cookie.match(new RegExp('(^|;\\s*)(' + CONSENT_COOKIE + ')=([^;]*)'));
    return match ? decodeURIComponent(match[3]) : null;
  }
}
