import { inject, Injectable } from '@angular/core';
import { AccessUser, Device, RoleName } from '../models';
import { ApiService } from './api.service';

export interface RoleRow {
  id: number;
  name: RoleName;
  label: string;
  editable: boolean;
  permissions: string[];
  locked: string[];
}

export interface RoleMatrix {
  roles: RoleRow[];
  catalog: { name: string; label: string }[];
}

/**
 * Usuarios con acceso, roles, matriz de permisos y celulares registrados.
 */
@Injectable({ providedIn: 'root' })
export class UserAccessService {
  private readonly api = inject(ApiService);

  users(): Promise<AccessUser[]> {
    return this.api.get<AccessUser[]>('users');
  }

  updateRoles(userId: number, roles: string[]): Promise<{ roles: string[] }> {
    return this.api.put(`users/${userId}/roles`, { roles });
  }

  updateAccess(
    userId: number,
    changes: { blocked?: boolean; app_access?: boolean; web_access?: boolean },
  ): Promise<unknown> {
    return this.api.patch(`users/${userId}/access`, changes);
  }

  roleMatrix(): Promise<RoleMatrix> {
    return this.api.get<RoleMatrix>('roles');
  }

  updateRolePermissions(role: RoleName, permissions: string[]): Promise<{ permissions: string[] }> {
    return this.api.put(`roles/${role}/permissions`, { permissions });
  }

  devices(): Promise<Device[]> {
    return this.api.get<Device[]>('devices');
  }

  approveDevice(id: number): Promise<Device> {
    return this.api.post<Device>(`devices/${id}/approve`);
  }

  revokeDevice(id: number): Promise<Device> {
    return this.api.post<Device>(`devices/${id}/revoke`);
  }
}
