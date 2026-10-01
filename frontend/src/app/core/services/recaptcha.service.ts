import { DOCUMENT } from '@angular/common';
import { inject, Injectable } from '@angular/core';
import { PublicConfigService } from './public-config.service';

interface Grecaptcha {
  ready(callback: () => void): void;
  execute(siteKey: string, options: { action: string }): Promise<string>;
}

declare global {
  interface Window {
    grecaptcha?: Grecaptcha;
  }
}

/** Acciones que Laravel valida (deben coincidir con new Recaptcha('...')). */
export type RecaptchaAction =
  | 'login'
  | 'register'
  | 'verify_email'
  | 'forgot_password'
  | 'reset_password';

/**
 * Google reCAPTCHA v3: invisible, sin casillas ni imágenes. Justo antes de
 * enviar el formulario pide un token para la acción y Laravel lo valida
 * con la clave secreta. Sin clave configurada (desarrollo) devuelve ''.
 */
@Injectable({ providedIn: 'root' })
export class RecaptchaService {
  private readonly document = inject(DOCUMENT);
  private readonly config = inject(PublicConfigService);
  private script: Promise<Grecaptcha> | null = null;

  /** Carga el script por adelantado (al abrir el formulario) para no demorar el envío. */
  async preload(): Promise<void> {
    const siteKey = await this.siteKey();
    if (siteKey) {
      await this.load(siteKey).catch(() => undefined);
    }
  }

  async token(action: RecaptchaAction): Promise<string> {
    const siteKey = await this.siteKey();
    if (!siteKey) {
      return '';
    }
    const grecaptcha = await this.load(siteKey);
    return grecaptcha.execute(siteKey, { action });
  }

  private async siteKey(): Promise<string | null> {
    try {
      return (await this.config.load()).recaptcha_site_key;
    } catch {
      return null;
    }
  }

  private load(siteKey: string): Promise<Grecaptcha> {
    this.script ??= new Promise<Grecaptcha>((resolve, reject) => {
      const done = () => window.grecaptcha!.ready(() => resolve(window.grecaptcha!));
      if (window.grecaptcha) {
        done();
        return;
      }
      const script = this.document.createElement('script');
      script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`;
      script.async = true;
      script.onload = done;
      script.onerror = () => {
        this.script = null;
        reject(new Error('No se pudo cargar reCAPTCHA.'));
      };
      this.document.head.appendChild(script);
    });
    return this.script;
  }
}
