/** Orientación de la credencial CR80 (tarjeta de crédito). */
export type CredentialOrientation = 'horizontal' | 'vertical';

/** Datos que dibuja la credencial; los da el detalle del empleado. */
export interface CredentialEmployee {
  /** Identificador cifrado para las URLs de la API. */
  public_id: string;
  employee_code: string;
  first_name: string;
  last_name: string;
  position: string | null;
  employment_type: 'permanent' | 'temporary';
  photo_url?: string | null;
  /** QR del frente, data URI SVG que genera el servidor. */
  badge_qr?: string | null;
  badge_issued_at?: string | null;
  badge_expires_on?: string | null;
  area?: { name: string } | null;
  office?: { name: string } | null;
}

/** Marca de la credencial: nombre y logo de la empresa. */
export interface CredentialCompany {
  name: string;
  logo_url?: string | null;
}
