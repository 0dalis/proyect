export interface AppTheme {

  name: string;

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

  fontFamily?: string; // <-- El "?" la hace opcional
  fontUrl?: string;
}