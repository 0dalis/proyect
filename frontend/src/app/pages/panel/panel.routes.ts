import { Routes } from '@angular/router';
import { accessGuard } from '../../core/guards/access.guard';

/**
 * Rutas del panel. Cada una declara en `data.access` quién puede entrar;
 * el menú lateral usa las mismas reglas (layout/navigation.ts).
 */
export const PANEL_ROUTES: Routes = [
  {
    path: '',
    title: 'Inicio',
    loadComponent: () =>
      import('./dashboard/dashboard.component').then((m) => m.DashboardComponent),
  },
  {
    path: 'perfil',
    title: 'Mi perfil',
    loadComponent: () => import('./profile/profile.component').then((m) => m.ProfileComponent),
  },
  {
    path: 'notificaciones',
    title: 'Notificaciones',
    loadComponent: () =>
      import('./notifications/notifications.component').then((m) => m.NotificationsComponent),
  },
  {
    path: 'solicitudes',
    title: 'Solicitudes',
    loadComponent: () => import('./requests/requests.component').then((m) => m.RequestsComponent),
  },
  {
    path: 'empleados',
    title: 'Empleados',
    canActivate: [accessGuard],
    data: { access: { permission: 'employees.view' } },
    loadComponent: () =>
      import('./employees/employees.component').then((m) => m.EmployeesComponent),
  },
  {
    // Antes de empleados/:id para que "organizar" no se tome como id
    path: 'empleados/organizar',
    title: 'Organizar empleados',
    canActivate: [accessGuard],
    data: { access: { permission: 'employees.manage' } },
    loadComponent: () =>
      import('./employees/organize/organize.component').then((m) => m.OrganizeEmployeesComponent),
  },
  {
    path: 'empleados/:id',
    title: 'Detalle del empleado',
    canActivate: [accessGuard],
    data: { access: { permission: 'employees.view' } },
    loadComponent: () =>
      import('./employee-detail/employee-detail.component').then((m) => m.EmployeeDetailComponent),
  },
  {
    path: 'bitacora',
    title: 'Bitácora',
    canActivate: [accessGuard],
    data: { access: { permission: 'audit.view' } },
    loadComponent: () => import('./activity/activity.component').then((m) => m.ActivityComponent),
  },
  {
    path: 'asistencia',
    title: 'Asistencia',
    loadComponent: () =>
      import('./attendance/attendance.component').then((m) => m.AttendanceComponent),
  },
  {
    path: 'reportes',
    title: 'Reportes',
    canActivate: [accessGuard],
    data: { access: { permission: 'reports.view' } },
    loadComponent: () => import('./reports/reports.component').then((m) => m.ReportsComponent),
  },
  {
    path: 'avisos',
    title: 'Enviar avisos',
    canActivate: [accessGuard],
    data: { access: { permission: 'announcements.send' } },
    loadComponent: () =>
      import('./announcements/announcements.component').then((m) => m.AnnouncementsComponent),
  },
  {
    path: 'prenomina',
    title: 'Pre-nómina',
    canActivate: [accessGuard],
    data: { access: { permission: 'payroll.manage', module: 'payroll' } },
    loadComponent: () => import('./payroll/payroll.component').then((m) => m.PayrollComponent),
  },
  {
    path: 'bonos',
    title: 'Bonos',
    canActivate: [accessGuard],
    data: { access: { permission: 'bonuses.manage', module: 'bonuses' } },
    loadComponent: () => import('./bonuses/bonuses.component').then((m) => m.BonusesComponent),
  },
  {
    path: 'oficinas',
    title: 'Oficinas y geocerca',
    canActivate: [accessGuard],
    data: { access: { permission: 'organization.manage' } },
    loadComponent: () => import('./offices/offices.component').then((m) => m.OfficesComponent),
  },
  {
    path: 'turnos-areas',
    title: 'Turnos y áreas',
    canActivate: [accessGuard],
    data: { access: { permission: 'organization.manage' } },
    loadComponent: () =>
      import('./shifts-areas/shifts-areas.component').then((m) => m.ShiftsAreasComponent),
  },
  {
    path: 'festivos',
    title: 'Días festivos',
    canActivate: [accessGuard],
    data: { access: { permission: 'organization.manage' } },
    loadComponent: () => import('./holidays/holidays.component').then((m) => m.HolidaysComponent),
  },
  {
    path: 'usuarios',
    title: 'Usuarios y roles',
    canActivate: [accessGuard],
    data: { access: { permission: 'users.manage' } },
    loadComponent: () => import('./users/users.component').then((m) => m.UsersComponent),
  },
  {
    path: 'kioskos',
    title: 'Kioskos',
    canActivate: [accessGuard],
    data: { access: { permission: 'kiosks.manage' } },
    loadComponent: () => import('./kiosks/kiosks.component').then((m) => m.KiosksComponent),
  },
  {
    path: 'suscripcion',
    title: 'Suscripción',
    canActivate: [accessGuard],
    data: { access: { roles: ['owner', 'admin'] } },
    loadComponent: () =>
      import('./subscription/subscription.component').then((m) => m.SubscriptionComponent),
  },
  {
    path: 'configuracion',
    title: 'Configuración',
    canActivate: [accessGuard],
    data: { access: { roles: ['owner'] } },
    loadComponent: () => import('./settings/settings.component').then((m) => m.SettingsComponent),
  },
  { path: '**', redirectTo: '' },
];
