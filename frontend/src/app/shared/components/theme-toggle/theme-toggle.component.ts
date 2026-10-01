import { Component, inject } from '@angular/core';
import { ThemeService } from '../../../core/services/theme.service';

/**
 * Botón sol / luna para cambiar entre modo claro y oscuro.
 */
@Component({
  selector: 'app-theme-toggle',
  templateUrl: './theme-toggle.component.html',
  styleUrl: './theme-toggle.component.scss',
})
export class ThemeToggleComponent {
  protected readonly theme = inject(ThemeService);
}
