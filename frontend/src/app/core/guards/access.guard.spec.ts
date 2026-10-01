import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { AuthService } from '../services/auth.service';
import { fakeUser } from '../testing/fake-user';
import { canAccess } from './access.guard';

describe('canAccess', () => {
  let auth: AuthService;

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [provideHttpClient()] });
    auth = TestBed.inject(AuthService);
    auth.user.set(fakeUser({ role: 'manager', permissions: ['employees.view', 'payroll.manage'] }));
  });

  it('allows routes without rules', () => {
    expect(canAccess(auth, undefined)).toBe(true);
  });

  it('checks permissions', () => {
    expect(canAccess(auth, { permission: 'employees.view' })).toBe(true);
    expect(canAccess(auth, { permission: 'users.manage' })).toBe(false);
  });

  it('checks roles', () => {
    expect(canAccess(auth, { roles: ['owner', 'admin'] })).toBe(false);
    expect(canAccess(auth, { roles: ['manager'] })).toBe(true);
  });

  it('requires the module to be on even with the permission', () => {
    expect(canAccess(auth, { permission: 'payroll.manage', module: 'payroll' })).toBe(true);
    auth.updateCompany({ payroll_enabled: false });
    expect(canAccess(auth, { permission: 'payroll.manage', module: 'payroll' })).toBe(false);
  });

  it('requires a linked employee when asked', () => {
    expect(canAccess(auth, { requiresEmployee: true })).toBe(false);
  });
});
