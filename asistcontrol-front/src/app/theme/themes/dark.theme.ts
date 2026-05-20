import { AppTheme } from '../theme.types';

export const DarkTheme: AppTheme = {

  name: 'dark',

  colors: {

    // =========================
    // PRIMARY (igual identidad visual)
    // =========================

    primaryDark: '44 76 212',
    primaryMedium: '0 110 235',
    primaryLight: '0 133 232',

    // =========================
    // ACCENTS (ligeramente más vivos en dark)
    // =========================

    accentBlue: '0 170 255',
    accentTeal: '0 200 180',

    // =========================
    // STATUS
    // =========================

    successGreen: '0 210 140',

    // =========================
    // BACKGROUNDS (dark UI real)
    // =========================

    background: '15 18 25',      // #0F1219
    surface: '28 32 43',         // #1C202B

    // =========================
    // TEXT (invertido vs light)
    // =========================

    textTitle: '245 247 250',    // casi blanco
    textBody: '180 187 196',     // gris claro

    placeholder: '120 127 140',
    borderFocus: '0 133 232',
  },

  fontFamily: 'Inter, sans-serif'
};