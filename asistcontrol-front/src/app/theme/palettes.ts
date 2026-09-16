import { AppTheme, ThemeMode, ThemePalette } from './theme.types';

export interface PaletteOption {
  key: ThemePalette;
  label: string;
  swatch: string;
}

export const PALETTE_OPTIONS: PaletteOption[] = [
  { key: 'indigo', label: 'Índigo', swatch: '#4f46e5' },
  { key: 'red', label: 'Rojo', swatch: '#dc2626' },
  { key: 'purple', label: 'Morado', swatch: '#9333ea' },
  { key: 'green', label: 'Verde', swatch: '#16a34a' },
  { key: 'blue', label: 'Azul', swatch: '#2563eb' },
  { key: 'orange', label: 'Naranja', swatch: '#ea580c' },
];

interface PaletteShades {
  // [primaryLight, primaryMedium, primaryDark]
  light: [string, string, string];
  dark: [string, string, string];
}

const SHADES: Record<ThemePalette, PaletteShades> = {
  indigo: {
    light: ['99 102 241', '79 70 229', '67 56 202'],
    dark: ['129 140 248', '99 102 241', '79 70 229'],
  },
  red: {
    light: ['239 68 68', '220 38 38', '185 28 28'],
    dark: ['248 113 113', '239 68 68', '220 38 38'],
  },
  purple: {
    light: ['168 85 247', '147 51 234', '126 34 206'],
    dark: ['192 132 252', '168 85 247', '147 51 234'],
  },
  green: {
    light: ['34 197 94', '22 163 74', '21 128 61'],
    dark: ['74 222 128', '34 197 94', '22 163 74'],
  },
  blue: {
    light: ['59 130 246', '37 99 235', '29 78 216'],
    dark: ['96 165 250', '59 130 246', '37 99 235'],
  },
  orange: {
    light: ['249 115 22', '234 88 12', '194 65 12'],
    dark: ['251 146 60', '249 115 22', '234 88 12'],
  },
};

const SUCCESS_GREEN = '16 185 129';

function buildTheme(palette: ThemePalette, mode: ThemeMode): AppTheme {
  const [primaryLight, primaryMedium, primaryDark] = SHADES[palette][mode];
  const isDark = mode === 'dark';

  return {
    name: `${palette}-${mode}`,
    palette,
    mode,
    colors: {
      primaryLight,
      primaryMedium,
      primaryDark,

      accentBlue: primaryLight,
      accentTeal: primaryDark,

      successGreen: SUCCESS_GREEN,

      background: isDark ? '2 6 23' : '245 247 250',
      surface: isDark ? '15 23 42' : '255 255 255',

      placeholder: isDark ? '100 116 139' : '148 163 184',

      textTitle: isDark ? '241 245 249' : '15 23 42',
      textBody: isDark ? '148 163 184' : '71 85 105',

      borderFocus: isDark ? primaryLight : primaryMedium,
    },
  };
}

export const PALETTES: Record<ThemePalette, Record<ThemeMode, AppTheme>> = {
  indigo: { light: buildTheme('indigo', 'light'), dark: buildTheme('indigo', 'dark') },
  red: { light: buildTheme('red', 'light'), dark: buildTheme('red', 'dark') },
  purple: { light: buildTheme('purple', 'light'), dark: buildTheme('purple', 'dark') },
  green: { light: buildTheme('green', 'light'), dark: buildTheme('green', 'dark') },
  blue: { light: buildTheme('blue', 'light'), dark: buildTheme('blue', 'dark') },
  orange: { light: buildTheme('orange', 'light'), dark: buildTheme('orange', 'dark') },
};

export function getTheme(palette: ThemePalette, mode: ThemeMode): AppTheme {
  return PALETTES[palette]?.[mode] ?? PALETTES.indigo.light;
}
