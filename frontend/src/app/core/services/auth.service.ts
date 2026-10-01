import { HttpErrorResponse } from '@angular/common/http';
import { computed, inject, Injectable, signal } from '@angular/core';
import { CurrentUser, Plan, RoleName } from '../models';
import { ApiService } from './api.service';
import { RecaptchaService } from './recaptcha.service';

export interface RegisterPayload {
  company_name: string;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  accept_terms: boolean;
}

/** Estado del enlace de confirmación del correo. */
export type VerificationStatus =
  | 'pending'
  | 'already_verified'
  | 'expired'
  | 'invalid'
  | 'verified';

export interface VerificationResult {
  status: VerificationStatus;
  message: string;
  /** Correo enmascarado (a***@empresa.com). */
  email: string | null;
}

/**
 * Sesión del panel. La sesión vive en una cookie HttpOnly que emite Laravel
 * (Sanctum SPA): el navegador la envía solo y JavaScript no puede leerla,
 * así que un script malicioso no puede robarla. Las peticiones que
 * modifican datos llevan el token CSRF (cookie XSRF-TOKEN → cabecera X-XSRF-TOKEN,
 * lo hace HttpClient automáticamente).
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly recaptcha = inject(RecaptchaService);

  readonly user = signal<CurrentUser | null>(null);
  /** Ya se preguntó al servidor si hay sesión (evita repetir /me sin sesión). */
  private checked = false;

  readonly isAuthenticated = computed(() => this.user() !== null);
  readonly isOwner = computed(() => this.user()?.role === 'owner');
  readonly role = computed<RoleName | null>(() => this.user()?.role ?? null);

  can(permission: string): boolean {
    return this.user()?.permissions?.includes(permission) ?? false;
  }

  hasRole(...roles: RoleName[]): boolean {
    const role = this.role();
    return role !== null && roles.includes(role);
  }

  moduleEnabled(module: 'payroll' | 'bonuses'): boolean {
    const company = this.user()?.company;
    return module === 'payroll' ? !!company?.payroll_enabled : !!company?.bonuses_enabled;
  }

  async login(email: string, password: string): Promise<CurrentUser> {
    const recaptchaToken = await this.recaptcha.token('login');
    await this.api.csrfCookie();
    await this.api.post('auth/login', {
      email,
      password,
      client: 'web',
      recaptcha_token: recaptchaToken,
    });

    // Confirma que el usuario puede usar el panel web (el dueño puede limitarlo a la app)
    try {
      return await this.refreshUser();
    } catch (error) {
      await this.logout().catch(() => undefined);
      throw error;
    }
  }

  /**
   * Devuelve el usuario si hay una sesión válida; null si no.
   */
  async loadUser(): Promise<CurrentUser | null> {
    if (this.user()) {
      return this.user();
    }
    if (this.checked) {
      return null;
    }
    try {
      return await this.refreshUser();
    } catch (error) {
      this.checked = true;
      if (!(error instanceof HttpErrorResponse) || error.status !== 401) {
        this.user.set(null);
      }
      return null;
    }
  }

  async refreshUser(): Promise<CurrentUser> {
    const user = this.normalize(await this.api.get<CurrentUser | { data: CurrentUser }>('me'));
    this.user.set(user);
    this.checked = true;
    return user;
  }

  updateCompany(changes: Partial<CurrentUser['company']>): void {
    this.user.update((user) =>
      user ? { ...user, company: { ...user.company, ...changes } } : user,
    );
  }

  /** Foto de la cuenta: se ve al instante en la barra superior. */
  updateAvatar(url: string | null): void {
    this.user.update((user) => (user ? { ...user, avatar_url: url } : user));
  }

  async logout(): Promise<void> {
    try {
      await this.api.post('auth/logout');
    } finally {
      this.clear();
    }
  }

  async register(payload: RegisterPayload): Promise<{ message: string }> {
    const recaptchaToken = await this.recaptcha.token('register');
    await this.api.csrfCookie();
    return this.api.post('auth/register', { ...payload, recaptcha_token: recaptchaToken });
  }

  /**
   * Enlace del correo de confirmación: `query` es la firma que puso Laravel
   * (expires y signature), tal cual llegó en la URL.
   */
  verificationStatus(id: string, hash: string, query: string): Promise<VerificationResult> {
    return this.api.get<VerificationResult>(`auth/email/verify/${id}/${hash}?${query}`);
  }

  async confirmEmail(id: string, hash: string, query: string): Promise<VerificationResult> {
    const recaptchaToken = await this.recaptcha.token('verify_email');
    await this.api.csrfCookie();
    return this.api.post<VerificationResult>(`auth/email/verify/${id}/${hash}?${query}`, {
      recaptcha_token: recaptchaToken,
    });
  }

  async resendVerification(email: string): Promise<{ message: string }> {
    await this.api.csrfCookie();
    return this.api.post('auth/email/resend', { email });
  }

  /** Olvidé mi contraseña: Laravel responde igual exista o no el correo. */
  async forgotPassword(email: string): Promise<{ message: string }> {
    const recaptchaToken = await this.recaptcha.token('forgot_password');
    await this.api.csrfCookie();
    return this.api.post('auth/password/forgot', { email, recaptcha_token: recaptchaToken });
  }

  async resetPassword(payload: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<{ message: string }> {
    const recaptchaToken = await this.recaptcha.token('reset_password');
    await this.api.csrfCookie();
    return this.api.post('auth/password/reset', { ...payload, recaptcha_token: recaptchaToken });
  }

  plans(): Promise<Plan[]> {
    return this.api.get<Plan[]>('plans');
  }

  /** Olvida la sesión local (el servidor ya la cerró o expiró). */
  clear(): void {
    this.user.set(null);
    this.checked = true;
  }

  /**
   * Acepta el usuario con o sin la envoltura "data" de Laravel y valida que
   * traiga lo mínimo que usa el panel, para no dejar la vista en blanco.
   */
  private normalize(response: CurrentUser | { data: CurrentUser }): CurrentUser {
    const user = 'data' in response ? response.data : response;
    if (!user || !Array.isArray(user.permissions) || !user.company) {
      throw new Error('Respuesta de usuario inválida');
    }
    return user;
  }
}
