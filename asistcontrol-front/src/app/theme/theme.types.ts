export type ThemePalette =
  | 'indigo'
  | 'red'
  | 'purple'
  | 'green'
  | 'blue'
  | 'orange';

export type ThemeMode = 'light' | 'dark';

export interface AppTheme {

  name: string;

  palette: ThemePalette;
  mode: ThemeMode;

  colors: {

    [key: string]: string;

    primaryDark: string;
    primaryMedium: string;
    primaryLight: string;

    accentBlue: string;
    accentTeal: string;

    successGreen: string;

    background: string;
    surface: string;

    placeholder: string;

    textTitle: string;
    textBody: string;

    borderFocus: string;
  };

  fontFamily?: string;
  fontUrl?: string;
}

export interface ThemePreferences {
  palette: ThemePalette;
  mode: ThemeMode;
  font: string;
}
