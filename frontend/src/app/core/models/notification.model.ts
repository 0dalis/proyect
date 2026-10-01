export type PanelNotificationType =
  | 'request_submitted'
  | 'request_approved'
  | 'request_rejected'
  | 'announcement';

/** Notificación de la campana del panel. */
export interface PanelNotification {
  id: number;
  type: PanelNotificationType;
  title: string;
  body: string | null;
  /** Ruta del panel a la que lleva. */
  link: string | null;
  read_at: string | null;
  created_at: string;
}
