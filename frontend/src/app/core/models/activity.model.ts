export interface ActivityChange {
  old: unknown;
  new: unknown;
}

export interface ActivityLogEntry {
  id: number;
  user_id: number | null;
  user_name: string;
  action: string;
  subject_type: string | null;
  subject_id: number | null;
  subject_label: string | null;
  /** Empleado relacionado; null en acciones generales (exportes, configuración). */
  employee_id: number | null;
  /** Token cifrado del empleado relacionado, para enlazarlo sin exponer su id. */
  employee_public_id: string | null;
  description: string;
  changes: Record<string, ActivityChange> | null;
  created_at: string;
}

export interface ActivityPage {
  data: ActivityLogEntry[];
  current_page: number;
  last_page: number;
  total: number;
  actions: Record<string, string>;
}
