export interface AppFont {
  key: string;
  label: string;
  fontFamily: string;
}

export const FONTS: AppFont[] = [
  { key: 'inter', label: 'Inter', fontFamily: "'Inter', sans-serif" },
  { key: 'poppins', label: 'Poppins', fontFamily: "'Poppins', sans-serif" },
  { key: 'montserrat', label: 'Montserrat', fontFamily: "'Montserrat', sans-serif" },
  { key: 'nunito', label: 'Nunito', fontFamily: "'Nunito', sans-serif" },
  { key: 'lora', label: 'Lora', fontFamily: "'Lora', serif" },
];

export const DEFAULT_FONT = 'inter';

export function getFont(key: string | null | undefined): AppFont {
  return FONTS.find((font) => font.key === key) ?? FONTS[0];
}
