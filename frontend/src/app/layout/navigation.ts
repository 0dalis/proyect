import { RouteAccess } from '../core/guards/access.guard';

export interface NavItem {
  path: string;
  label: string;
  /** Nombre de Bootstrap Icons sin el prefijo bi- (house-door, people…) */
  icon: string;
  access?: RouteAccess;
}

export interface NavGroup {
  label: string;
  items: NavItem[];
}

/**
 * Menú lateral. Las reglas de `access` son las mismas que protegen cada ruta
 * en panel.routes.ts, así que nunca se muestra un enlace que el guard rechace.
 */
export const PANEL_NAVIGATION: NavGroup[] = [
  {
    label: 'Mi espacio',
    items: [
      { path: '/panel', label: 'Inicio', icon: 'house-door' },
      { path: '/panel/perfil', label: 'Mi perfil', icon: 'person-circle' },
      { path: '/panel/notificaciones', label: 'Notificaciones', icon: 'bell' },
      { path: '/panel/solicitudes', label: 'Solicitudes', icon: 'file-earmark-text' },
    ],
  },
  {
    label: 'Equipo',
    items: [
      {
        path: '/panel/empleados',
        label: 'Empleados',
        icon: 'people',
        access: { permission: 'employees.view' },
      },
      { path: '/panel/asistencia', label: 'Asistencia', icon: 'clock-history' },
      {
        path: '/panel/reportes',
        label: 'Reportes',
        icon: 'bar-chart-line',
        access: { permission: 'reports.view' },
      },
      {
        path: '/panel/avisos',
        label: 'Enviar avisos',
        icon: 'megaphone',
        access: { permission: 'announcements.send' },
      },
    ],
  },
  {
    label: 'Nómina',
    items: [
      {
        path: '/panel/prenomina',
        label: 'Pre-nómina',
        icon: 'cash-stack',
        access: { permission: 'payroll.manage', module: 'payroll' },
      },
      {
        path: '/panel/bonos',
        label: 'Bonos',
        icon: 'award',
        access: { permission: 'bonuses.manage', module: 'bonuses' },
      },
    ],
  },
  {
    label: 'Empresa',
    items: [
      {
        path: '/panel/oficinas',
        label: 'Oficinas y geocerca',
        icon: 'geo-alt',
        access: { permission: 'organization.manage' },
      },
      {
        path: '/panel/turnos-areas',
        label: 'Turnos y áreas',
        icon: 'calendar-week',
        access: { permission: 'organization.manage' },
      },
      {
        path: '/panel/festivos',
        label: 'Días festivos',
        icon: 'calendar-heart',
        access: { permission: 'organization.manage' },
      },
      {
        path: '/panel/usuarios',
        label: 'Usuarios y roles',
        icon: 'shield-lock',
        access: { permission: 'users.manage' },
      },
      {
        path: '/panel/kioskos',
        label: 'Kioskos',
        icon: 'display',
        access: { permission: 'kiosks.manage' },
      },
      {
        path: '/panel/bitacora',
        label: 'Bitácora',
        icon: 'journal-text',
        access: { permission: 'audit.view' },
      },
      {
        path: '/panel/suscripcion',
        label: 'Suscripción',
        icon: 'credit-card',
        access: { roles: ['owner', 'admin'] },
      },
      {
        path: '/panel/configuracion',
        label: 'Configuración',
        icon: 'gear',
        access: { roles: ['owner'] },
      },
    ],
  },
];
