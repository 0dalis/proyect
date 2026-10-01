import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { ThemeToggleComponent } from '../../../shared/components/theme-toggle/theme-toggle.component';
import { LEGAL, LEGAL_LINKS } from '../../../shared/constants/legal';

/**
 * Marco común de términos, privacidad y cookies.
 */
@Component({
  selector: 'app-legal-layout',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, ThemeToggleComponent],
  templateUrl: './legal-layout.component.html',
  styleUrl: './legal-layout.component.scss',
})
export class LegalLayoutComponent {
  protected readonly legal = LEGAL;
  protected readonly links = LEGAL_LINKS;
  protected readonly year = new Date().getFullYear();
}
