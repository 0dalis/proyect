import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

import { ThemeService } from './theme/theme.service';
import { CookieConsentComponent } from './shared/cookie-consent/cookie-consent.component';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, CookieConsentComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent {

  title = 'asistcontrol-front';

  constructor(private themeService: ThemeService) {
    this.themeService.applyCurrent();
  }
}
