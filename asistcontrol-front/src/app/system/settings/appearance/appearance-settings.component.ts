import { Component } from '@angular/core';

import { ThemeSettingsComponent } from '../../../theme/theme-settings/theme-settings.component';

@Component({
  selector: 'app-appearance-settings',
  standalone: true,
  imports: [ThemeSettingsComponent],
  templateUrl: './appearance-settings.component.html',
  styleUrl: './appearance-settings.component.css'
})
export class AppearanceSettingsComponent {}
