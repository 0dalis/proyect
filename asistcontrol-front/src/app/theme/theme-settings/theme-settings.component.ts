import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';

import { ThemeService } from '../theme.service';
import { PALETTE_OPTIONS } from '../palettes';
import { FONTS } from '../fonts';
import { ThemeMode, ThemePalette } from '../theme.types';

@Component({
  selector: 'app-theme-settings',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './theme-settings.component.html',
  styleUrl: './theme-settings.component.css'
})
export class ThemeSettingsComponent implements OnInit {

  palettes = PALETTE_OPTIONS;
  fonts = FONTS;

  selectedPalette: ThemePalette = 'indigo';
  selectedMode: ThemeMode = 'light';
  selectedFont = 'inter';

  constructor(private themeService: ThemeService) {}

  ngOnInit(): void {
    this.selectedPalette = this.themeService.getPalette();
    this.selectedMode = this.themeService.getMode();
    this.selectedFont = this.themeService.getFontKey();
  }

  selectPalette(palette: ThemePalette): void {
    this.selectedPalette = palette;
    this.themeService.setPalette(palette);
  }

  selectMode(mode: ThemeMode): void {
    this.selectedMode = mode;
    this.themeService.setMode(mode);
  }

  selectFont(font: string): void {
    this.selectedFont = font;
    this.themeService.setFont(font);
  }
}
