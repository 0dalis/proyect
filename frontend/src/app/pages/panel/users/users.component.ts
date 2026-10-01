import { DatePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { AccessUser, Device, RoleName } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { RoleRow, UserAccessService } from '../../../core/services/user-access.service';
import { errorMessage } from '../../../core/utils/error-message';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { ToastService } from '../../../core/services/toast.service';
import { DialogService } from '../../../core/services/dialog.service';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

@Component({
  selector: 'app-users',
  imports: [SkeletonComponent, DatePipe, PageHeaderComponent],
  templateUrl: './users.component.html',
  styleUrl: './users.component.scss',
})
export class UsersComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly dialog = inject(DialogService);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);
  private readonly userAccess = inject(UserAccessService);

  protected readonly users = signal<AccessUser[]>([]);
  protected readonly devices = signal<Device[]>([]);
  protected readonly roles = signal<RoleRow[]>([]);
  protected readonly catalog = signal<{ name: string; label: string }[]>([]);

  private readonly labels: Record<RoleName, string> = {
    owner: 'Dueño',
    admin: 'Administrador',
    manager: 'Gerente',
    employee: 'Empleado',
  };

  protected readonly assignable = computed(() => {
    const roles: RoleName[] = this.auth.isOwner()
      ? ['employee', 'manager', 'admin']
      : ['employee', 'manager'];
    return roles.map((value) => ({ value, label: this.labels[value] }));
  });

  async ngOnInit(): Promise<void> {
    await this.load();
    if (this.auth.can('roles.assign')) {
      const data = await this.userAccess.roleMatrix();
      this.roles.set(data.roles.filter((role) => role.name !== 'employee'));
      this.catalog.set(data.catalog);
    }
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    const [users, devices] = await Promise.all([
      this.userAccess.users(),
      this.userAccess.devices(),
    ]);
    this.users.set(users);
    this.devices.set(devices);
  }

  protected roleLabel(role: RoleName): string {
    return this.labels[role];
  }

  protected isAssignable(role: RoleName): boolean {
    return this.assignable().some((r) => r.value === role);
  }

  /** El dueño no se toca; un admin no puede modificar a otro admin. */
  protected canChange(user: AccessUser): boolean {
    if (user.role === 'owner' || user.id === this.auth.user()?.id) {
      return false;
    }
    return this.auth.isOwner() || user.role !== 'admin';
  }

  protected async changeRole(user: AccessUser, event: Event): Promise<void> {
    const select = event.target as HTMLSelectElement;
    const role = select.value as RoleName;

    if (role === 'admin') {
      const confirmed = await this.dialog.confirm({
        title: `¿Nombrar administrador a ${user.name}?`,
        text: 'Podrá gestionar empleados, vacaciones, sueldos y bonos, y asignar gerentes.',
        confirmText: 'Nombrar administrador',
      });
      if (!confirmed) {
        select.value = user.role;
        return;
      }
    }

    await this.run(
      () => this.userAccess.updateRoles(user.id, role === 'employee' ? [] : [role]),
      `${user.name} ahora es ${this.roleLabel(role).toLowerCase()}.`,
      'Rol actualizado',
    );
  }

  protected toggle(
    user: AccessUser,
    field: 'app_access' | 'web_access',
    event: Event,
  ): Promise<void> {
    const enabled = (event.target as HTMLInputElement).checked;
    const channel = field === 'app_access' ? 'la app' : 'el panel web';
    return this.run(
      () => this.userAccess.updateAccess(user.id, { [field]: enabled }),
      `${user.name} ${enabled ? 'ya puede' : 'ya no puede'} entrar a ${channel}.`,
      'Acceso actualizado',
    );
  }

  protected async toggleBlock(user: AccessUser): Promise<void> {
    const blocking = !user.blocked_at;

    if (blocking) {
      const confirmed = await this.dialog.confirm({
        title: `¿Bloquear a ${user.name}?`,
        text: 'Se cerrarán sus sesiones y se revocará su celular. Su historial de asistencia se conserva.',
        confirmText: 'Bloquear acceso',
        variant: 'danger',
      });
      if (!confirmed) {
        return;
      }
    }

    await this.run(
      () => this.userAccess.updateAccess(user.id, { blocked: blocking }),
      blocking ? `${user.name} ya no puede entrar.` : `${user.name} puede volver a entrar.`,
      blocking ? 'Usuario bloqueado' : 'Usuario desbloqueado',
    );
  }

  protected async togglePermission(role: RoleRow, permission: string): Promise<void> {
    const permissions = role.permissions.includes(permission)
      ? role.permissions.filter((p) => p !== permission)
      : [...role.permissions, permission];
    try {
      const result = await this.userAccess.updateRolePermissions(role.name, permissions);
      this.toast.success(this.catalog().find((c) => c.name === permission)?.label ?? permission, {
        title: permissions.includes(permission)
          ? `Permiso otorgado a ${role.label}`
          : `Permiso quitado a ${role.label}`,
        duration: 2500,
      });
      this.roles.update((rows) =>
        rows.map((r) => (r.id === role.id ? { ...r, permissions: result.permissions } : r)),
      );
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async deviceAction(device: Device, action: 'approve' | 'revoke'): Promise<void> {
    const owner = `${device.employee?.first_name ?? ''} ${device.employee?.last_name ?? ''}`.trim();

    if (action === 'revoke') {
      const confirmed = await this.dialog.confirm({
        title: '¿Revocar este celular?',
        text: `${owner} no podrá checar desde "${device.name || device.device_identifier}" hasta que lo autorices de nuevo.`,
        confirmText: 'Revocar',
        variant: 'danger',
      });
      if (!confirmed) {
        return;
      }
    }

    await this.run(
      () =>
        action === 'approve'
          ? this.userAccess.approveDevice(device.id)
          : this.userAccess.revokeDevice(device.id),
      action === 'approve'
        ? `${owner} ya puede checar desde este celular.`
        : `${owner} ya no puede checar desde ese celular.`,
      action === 'approve' ? 'Celular autorizado' : 'Celular revocado',
    );
  }

  private async run(
    action: () => Promise<unknown>,
    success?: string,
    title?: string,
  ): Promise<void> {
    try {
      await action();
      if (success) {
        this.toast.success(success, { title });
      }
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
    await this.load();
  }
}
