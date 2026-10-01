import { DOCUMENT } from '@angular/common';
import { computed, inject, Injectable, signal } from '@angular/core';

export type ThemeMode = 'light' | 'dark' | 'system';
export type ThemeAccent = 'blue' | 'indigo' | 'purple' | 'green' | 'teal' | 'orange' | 'rose';
export type ThemeFont = 'inter' | 'poppins' | 'montserrat' | 'nunito' | 'lora';

const STORAGE_KEY = 'asist.theme';
const ACCENT_KEY = 'asist.accent';
const FONT_KEY = 'asist.font';

/** Colores de acento (styles.css → html[data-accent]). Azul = corporativo. */
export const ACCENTS: { key: ThemeAccent; label: string; swatch: string }[] = [
  { key: 'blue', label: 'Azul corporativo', swatch: '#3b82f6' },
  { key: 'indigo', label: 'Índigo', swatch: '#6366f1' },
  { key: 'purple', label: 'Morado', swatch: '#9333ea' },
  { key: 'teal', label: 'Turquesa', swatch: '#0d9488' },
  { key: 'green', label: 'Verde', swatch: '#16a34a' },
  { key: 'orange', label: 'Naranja', swatch: '#ea580c' },
  { key: 'rose', label: 'Rosa', swatch: '#e11d48' },
];

/** Tipografías (Inter viene en index.html; las demás se cargan al elegirlas). */
export const FONTS: { key: ThemeFont; label: string; family: string; google?: string }[] = [
  { key: 'inter', label: 'Inter', family: "'Inter', sans-serif" },
  { key: 'poppins', label: 'Poppins', family: "'Poppins', sans-serif", google: 'Poppins' },
  { key: 'montserrat', label: 'Montserrat', family: "'Montserrat', sans-serif", google: 'Montserrat' },
  { key: 'nunito', label: 'Nunito', family: "'Nunito', sans-serif", google: 'Nunito' },
  { key: 'lora', label: 'Lora', family: "'Lora', serif", google: 'Lora' },
];

/**
 * Apariencia de este navegador: modo claro / oscuro / sistema, color de
 * acento y tipografía. index.html aplica lo guardado antes de que cargue
 * Angular para no parpadear.
 */
@Injectable({ providedIn: 'root' })
export class ThemeService {
  private readonly document = inject(DOCUMENT);
  private readonly media = this.document.defaultView?.matchMedia?.('(prefers-color-scheme: dark)');
  private readonly systemDark = signal(this.media?.matches ?? false);

  readonly mode = signal<ThemeMode>(this.readMode());
  readonly accent = signal<ThemeAccent>(this.read(ACCENT_KEY, ACCENTS.map((a) => a.key), 'blue'));
  readonly font = signal<ThemeFont>(this.read(FONT_KEY, FONTS.map((f) => f.key), 'inter'));
  readonly isDark = computed(() =>
    this.mode() === 'system' ? this.systemDark() : this.mode() === 'dark',
  );

  constructor() {
    this.media?.addEventListener?.('change', (event) => {
      this.systemDark.set(event.matches);
      this.apply(false);
    });
    this.apply(false);
    this.applyAccent();
    this.applyFont();
  }

  setMode(mode: ThemeMode): void {
    this.mode.set(mode);
    this.store(STORAGE_KEY, mode);
    this.apply(true);
  }

  toggle(): void {
    this.setMode(this.isDark() ? 'light' : 'dark');
  }

  setAccent(accent: ThemeAccent): void {
    this.accent.set(accent);
    this.store(ACCENT_KEY, accent);
    this.withTransition();
    this.applyAccent();
  }

  setFont(font: ThemeFont): void {
    this.font.set(font);
    this.store(FONT_KEY, font);
    this.applyFont();
  }

  private apply(animate: boolean): void {
    if (animate) {
      this.withTransition();
    }
    this.document.documentElement.classList.toggle('dark', this.isDark());
  }

  private applyAccent(): void {
    const root = this.document.documentElement;
    if (this.accent() === 'blue') {
      root.removeAttribute('data-accent');
    } else {
      root.setAttribute('data-accent', this.accent());
    }
  }

  private applyFont(): void {
    const root = this.document.documentElement;
    const font = FONTS.find((f) => f.key === this.font()) ?? FONTS[0];

    if (!font.google) {
      root.removeAttribute('data-font');
      return;
    }

    let link = this.document.getElementById('asist-font') as HTMLLinkElement | null;
    if (!link) {
      link = this.document.createElement('link');
      link.id = 'asist-font';
      link.rel = 'stylesheet';
      this.document.head.appendChild(link);
    }
    link.href = `https://fonts.googleapis.com/css2?family=${font.google}:wght@400;500;600;700;800&display=swap`;
    root.setAttribute('data-font', font.key);
  }

  private withTransition(): void {
    const root = this.document.documentElement;
    root.classList.add('theme-transition');
    setTimeout(() => root.classList.remove('theme-transition'), 300);
  }

  private readMode(): ThemeMode {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      return saved === 'light' || saved === 'dark' ? saved : 'system';
    } catch {
      return 'system';
    }
  }

  private read<T extends string>(key: string, allowed: T[], fallback: T): T {
    try {
      const saved = localStorage.getItem(key) as T | null;
      return saved && allowed.includes(saved) ? saved : fallback;
    } catch {
      return fallback;
    }
  }

  private store(key: string, value: string): void {
    try {
      localStorage.setItem(key, value);
    } catch {
      // Sin almacenamiento: dura lo que la pestaña
    }
  }
}
