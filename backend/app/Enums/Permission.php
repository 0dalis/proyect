<?php

namespace App\Enums;

enum Permission: string
{
    case EmployeesView = 'employees.view';
    case EmployeesManage = 'employees.manage';
    case AttendanceView = 'attendance.view';
    case AttendanceManage = 'attendance.manage';
    case RequestsView = 'requests.view';
    case RequestsApprove = 'requests.approve';
    case VacationsApprove = 'vacations.approve';
    case UsersManage = 'users.manage';
    case RolesAssign = 'roles.assign';
    case AnnouncementsSend = 'announcements.send';
    case OrganizationManage = 'organization.manage';
    case KiosksManage = 'kiosks.manage';
    case PayrollManage = 'payroll.manage';
    case BonusesManage = 'bonuses.manage';
    case ReportsView = 'reports.view';
    case AuditView = 'audit.view';

    public function label(): string
    {
        return match ($this) {
            self::EmployeesView => 'Ver empleados',
            self::EmployeesManage => 'Crear y editar empleados',
            self::AttendanceView => 'Ver asistencia',
            self::AttendanceManage => 'Corregir asistencia',
            self::RequestsView => 'Ver solicitudes y justificaciones',
            self::RequestsApprove => 'Autorizar justificaciones, retardos y salidas',
            self::VacationsApprove => 'Autorizar vacaciones',
            self::UsersManage => 'Dar o bloquear acceso a usuarios',
            self::RolesAssign => 'Asignar roles',
            self::AnnouncementsSend => 'Enviar notificaciones y noticias',
            self::OrganizationManage => 'Oficinas, turnos y áreas',
            self::KiosksManage => 'Kioskos y credenciales',
            self::PayrollManage => 'Sueldos',
            self::BonusesManage => 'Bonos y sus reglas',
            self::ReportsView => 'Reportes',
            self::AuditView => 'Ver la bitácora de actividad',
        };
    }

    /**
     * Permisos iniciales de cada rol. El Owner siempre los tiene todos.
     *
     * @return list<self>
     */
    public static function defaultsFor(Role $role): array
    {
        return match ($role) {
            Role::Owner => self::cases(),
            // La bitácora es del dueño; puede otorgarla a administradores
            Role::Admin => array_values(array_filter(self::cases(), fn (self $permission) => $permission !== self::AuditView)),
            Role::Manager => [self::EmployeesView, self::AttendanceView, self::RequestsView, self::RequestsApprove, self::ReportsView],
            Role::Employee => [],
        };
    }

    /**
     * Permisos que nunca pueden darse a un rol, aunque el Owner lo intente.
     *
     * @return list<self>
     */
    public static function lockedFor(Role $role): array
    {
        return match ($role) {
            Role::Manager => [self::VacationsApprove, self::RolesAssign, self::UsersManage, self::PayrollManage, self::BonusesManage, self::AuditView],
            Role::Employee => self::cases(),
            default => [],
        };
    }
}
