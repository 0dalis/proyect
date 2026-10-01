import { TestBed } from '@angular/core/testing';
import { ThemeService } from './theme.service';

describe('ThemeService', () => {
  afterEach(() => {
    localStorage.clear();
    document.documentElement.classList.remove('dark');
    document.documentElement.removeAttribute('data-accent');
    document.documentElement.removeAttribute('data-font');
    document.getElementById('asist-font')?.remove();
  });

  it('switches between light and dark and remembers the choice', () => {
    const theme = TestBed.inject(ThemeService);

    theme.setMode('dark');
    expect(document.documentElement.classList.contains('dark')).toBe(true);
    expect(localStorage.getItem('asist.theme')).toBe('dark');

    theme.toggle();
    expect(theme.isDark()).toBe(false);
    expect(document.documentElement.classList.contains('dark')).toBe(false);
    expect(localStorage.getItem('asist.theme')).toBe('light');
  });

  it('starts from the saved mode', () => {
    localStorage.setItem('asist.theme', 'dark');

    expect(TestBed.inject(ThemeService).mode()).toBe('dark');
  });

  it('applies the accent color and keeps corporate blue as the default', () => {
    const theme = TestBed.inject(ThemeService);
    const root = document.documentElement;

    expect(theme.accent()).toBe('blue');
    expect(root.hasAttribute('data-accent')).toBe(false);

    theme.setAccent('teal');
    expect(root.getAttribute('data-accent')).toBe('teal');
    expect(localStorage.getItem('asist.accent')).toBe('teal');

    theme.setAccent('blue');
    expect(root.hasAttribute('data-accent')).toBe(false);
  });

  it('loads the chosen font from Google Fonts only when it is not Inter', () => {
    const theme = TestBed.inject(ThemeService);

    theme.setFont('poppins');
    expect(document.documentElement.getAttribute('data-font')).toBe('poppins');
    expect((document.getElementById('asist-font') as HTMLLinkElement).href).toContain('family=Poppins');

    theme.setFont('inter');
    expect(document.documentElement.hasAttribute('data-font')).toBe(false);
  });

  it('ignores unknown saved values', () => {
    localStorage.setItem('asist.accent', 'fucsia-neon');

    expect(TestBed.inject(ThemeService).accent()).toBe('blue');
  });
});
