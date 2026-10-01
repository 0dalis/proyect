import { RoleName } from './user.model';

export interface AccessUser {
  id: number;
  name: string;
  email: string;
  employee_id: number | null;
  role: RoleName;
  roles: string[];
  app_access: boolean;
  web_access: boolean;
  blocked_at: string | null;
  last_login_at: string | null;
}
