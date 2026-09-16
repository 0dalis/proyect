import { Component, EventEmitter, Output, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';

import { ThemeSettingsComponent } from '../../../theme/theme-settings/theme-settings.component';

@Component({
  selector: 'app-setup-menu',
  standalone: true,
  imports: [CommonModule, ThemeSettingsComponent],
  templateUrl: './setup-menu.component.html',
  styleUrl: './setup-menu.component.css'
})
export class SetupMenuComponent {

  @Output() logout = new EventEmitter<void>();

  settingsOpen = false;

  toggleSettings(): void {
    this.settingsOpen = !this.settingsOpen;
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    if (!target.closest('.setup-menu-settings')) {
      this.settingsOpen = false;
    }
  }
}
