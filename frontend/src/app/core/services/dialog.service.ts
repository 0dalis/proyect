import { Injectable } from '@angular/core';
import Swal, { SweetAlertIcon } from 'sweetalert2';

export type DialogVariant = 'primary' | 'danger' | 'warning';

export interface ConfirmOptions {
  title: string;
  text?: string;
  confirmText?: string;
  cancelText?: string;
  variant?: DialogVariant;
  icon?: SweetAlertIcon;
}

export interface PromptOptions extends ConfirmOptions {
  inputLabel?: string;
  placeholder?: string;
  /** Si es obligatorio escribir algo para confirmar. */
  required?: boolean;
}

const ICON_COLORS: Record<DialogVariant, string> = {
  primary: '#3b82f6',
  danger: '#ef4444',
  warning: '#f59e0b',
};

const CONFIRM_CLASSES: Record<DialogVariant, string> = {
  primary: 'btn btn-primary',
  danger: 'btn btn-primary !border-danger !bg-danger hover:!bg-danger-text',
  warning: 'btn btn-primary !border-warning !bg-warning hover:!bg-warning-text',
};

/**
 * Diálogos que piden una decisión (confirmar, escribir un motivo), con
 * SweetAlert2 y los estilos de la aplicación. Para avisos que no piden
 * decisión se usa ToastService.
 */
@Injectable({ providedIn: 'root' })
export class DialogService {
  private readonly base = Swal.mixin({
    buttonsStyling: false,
    reverseButtons: true,
    focusCancel: true,
    customClass: {
      popup: 'app-swal',
      title: 'app-swal__title',
      htmlContainer: 'app-swal__text',
      actions: 'app-swal__actions',
      cancelButton: 'btn',
      input: 'app-swal__input',
      inputLabel: 'app-swal__label',
      validationMessage: 'app-swal__validation',
    },
  });

  async confirm(options: ConfirmOptions): Promise<boolean> {
    const variant = options.variant ?? 'primary';
    const result = await this.base.fire({
      title: options.title,
      text: options.text,
      icon: options.icon ?? (variant === 'primary' ? 'question' : 'warning'),
      iconColor: ICON_COLORS[variant],
      showCancelButton: true,
      confirmButtonText: options.confirmText ?? 'Confirmar',
      cancelButtonText: options.cancelText ?? 'Cancelar',
      customClass: { confirmButton: CONFIRM_CLASSES[variant] },
    });
    return result.isConfirmed;
  }

  /**
   * Confirmación con un campo de texto (por ejemplo, motivo de rechazo).
   * Devuelve null si se canceló.
   */
  async prompt(options: PromptOptions): Promise<string | null> {
    const variant = options.variant ?? 'primary';
    const result = await this.base.fire({
      title: options.title,
      text: options.text,
      icon: options.icon,
      iconColor: ICON_COLORS[variant],
      input: 'textarea',
      inputLabel: options.inputLabel,
      inputPlaceholder: options.placeholder,
      showCancelButton: true,
      confirmButtonText: options.confirmText ?? 'Confirmar',
      cancelButtonText: options.cancelText ?? 'Cancelar',
      customClass: { confirmButton: CONFIRM_CLASSES[variant] },
      inputValidator: (value) => (options.required && !value?.trim() ? 'Escribe un motivo.' : null),
    });
    return result.isConfirmed ? String(result.value ?? '').trim() : null;
  }
}
