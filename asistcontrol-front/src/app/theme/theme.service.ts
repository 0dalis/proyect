import { Injectable } from '@angular/core';

import { AppTheme, ThemeMode, ThemePalette, ThemePreferences } from './theme.types';
import { getTheme } from './palettes';
import { DEFAULT_FONT, getFont } from './fonts';

const PREFS_COOKIE = 'ac_prefs';
const PREFS_STORAGE = 'ac_prefs';
const COOKIE_DAYS = 365;

@Injectable({
  providedIn: 'root'
})
export class ThemeService {

  private currentTheme: AppTheme = getTheme('indigo', 'light');

  constructor() {
    this.ensurePreferences();
  }

  // ---------- Preferencias ----------

  private defaults(): ThemePreferences {
    return { palette: 'indigo', mode: 'light', font: DEFAULT_FONT };
  }

  private readRaw(): Partial<ThemePreferences> {
    const raw = this.readCookie(PREFS_COOKIE) ?? localStorage.getItem(PREFS_STORAGE);
    if (!raw) return {};
    try {
      return JSON.parse(raw) as Partial<ThemePreferences>;
    } catch {
      return {};
    }
  }

  getPreferences(): ThemePreferences {
    const raw = this.readRaw();
    const defaults = this.defaults();
    return {
      palette: (raw.palette as ThemePalette) ?? defaults.palette,
      mode: (raw.mode as ThemeMode) ?? defaults.mode,
      font: raw.font ?? defaults.font,
    };
  }

  private savePreferences(prefs: ThemePreferences): void {
    const value = JSON.stringify(prefs);
    this.writeCookie(PREFS_COOKIE, value, COOKIE_DAYS);
    localStorage.setItem(PREFS_STORAGE, value);
  }

  private ensurePreferences(): void {
    const prefs = this.getPreferences();
    this.savePreferences(prefs);
  }

  getPalette(): ThemePalette {
    return this.getPreferences().palette;
  }

  getMode(): ThemeMode {
    return this.getPreferences().mode;
  }

  getFontKey(): string {
    return this.getPreferences().font;
  }

  // ---------- Setters (cambio en tiempo real) ----------

  setPalette(palette: ThemePalette): void {
    const prefs = { ...this.getPreferences(), palette };
    this.savePreferences(prefs);
    this.applyCurrent();
  }

  setMode(mode: ThemeMode): void {
    const prefs = { ...this.getPreferences(), mode };
    this.savePreferences(prefs);
    this.applyCurrent();
  }

  setFont(font: string): void {
    const prefs = { ...this.getPreferences(), font };
    this.savePreferences(prefs);
    this.applyFont(font);
  }

  toggleMode(): void {
    this.setMode(this.getMode() === 'dark' ? 'light' : 'dark');
  }

  // Compatibilidad con la API previa.
  setThemeMode(mode: string): void {
    this.setMode(mode === 'dark' ? 'dark' : 'light');
  }

  applyCurrent(): void {
    const prefs = this.getPreferences();
    this.applyTheme(getTheme(prefs.palette, prefs.mode));
    this.applyFont(prefs.font);
  }

  applyFont(fontKey: string): void {
    document.documentElement.style.setProperty('--user-font', getFont(fontKey).fontFamily);
  }

  // ---------- Aplicación del tema ----------

  applyTheme(theme: AppTheme): void {
    this.currentTheme = theme;

    Object.keys(theme.colors).forEach((key) => {
      const cssVarName = `--${this.toKebabCase(key)}`;
      document.documentElement.style.setProperty(cssVarName, theme.colors[key]);
    });

    if (theme.fontFamily) {
      document.documentElement.style.setProperty('--user-font', theme.fontFamily);
    }

    localStorage.setItem('current-theme-name', theme.name);
  }

  getCurrentTheme(): AppTheme {
    return this.currentTheme;
  }

  // ---------- Cookies ----------

  private writeCookie(name: string, value: string, days: number): void {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
  }

  private readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
    return match ? decodeURIComponent(match[3]) : null;
  }

  private toKebabCase(str: string): string {
    return str.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
  }
}
