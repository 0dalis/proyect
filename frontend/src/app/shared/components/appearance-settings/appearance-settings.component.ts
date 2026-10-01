import { Component, inject } from '@angular/core';
import { ACCENTS, FONTS, ThemeMode, ThemeService } from '../../../core/services/theme.service';

/**
 * Apariencia (Mi perfil): modo, color de acento y tipografía. Se guarda en
 * este navegador y se aplica al momento.
 */
@Component({
  selector: 'app-appearance-settings',
  templateUrl: './appearance-settings.component.html',
  styleUrl: './appearance-settings.component.scss',
})
export class AppearanceSettingsComponent {
  protected readonly theme = inject(ThemeService);
  protected readonly accents = ACCENTS;
  protected readonly fonts = FONTS;
  protected readonly modes: { key: ThemeMode; label: string; icon: string }[] = [
    { key: 'light', label: 'Claro', icon: 'sun' },
    { key: 'dark', label: 'Oscuro', icon: 'moon-stars' },
    { key: 'system', label: 'Sistema', icon: 'circle-half' },
  ];
}
