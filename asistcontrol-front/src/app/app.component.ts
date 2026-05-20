import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

import { ThemeService } from './theme/theme.service';

import { DefaultTheme } from './theme/themes/default.theme';
import { DarkTheme } from './theme/themes/dark.theme';

type ThemeMode = 'default' | 'dark';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent {

  title = 'asistcontrol-front';

  constructor(private themeService: ThemeService,) {

    let mode = localStorage.getItem('theme-mode') as ThemeMode | null;

    if (!mode) {
      mode = 'default';
      localStorage.setItem('theme-mode', mode);
    }
    switch (mode) {
      case 'dark':
        this.themeService.applyTheme(DarkTheme);
        break;
      case 'default':
      default:
        this.themeService.applyTheme(DefaultTheme);
        break;
    }
  }
}