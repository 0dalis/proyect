import { Component, computed, input } from '@angular/core';
import { CredentialCompany, CredentialEmployee, CredentialOrientation } from '../../../core/models';
import { initialsOf } from '../image-upload/image-upload.component';

/**
 * Una cara de la credencial (frente o reverso) al tamaño real de una tarjeta
 * CR80. Los mismos números viven en resources/views/pdf/badges.blade.php, que
 * es el PDF de la impresión masiva.
 */
@Component({
  selector: 'app-credential-card',
  templateUrl: './credential-card.component.html',
  styleUrl: './credential-card.component.scss',
})
export class CredentialCardComponent {
  readonly employee = input.required<CredentialEmployee>();
  readonly company = input.required<CredentialCompany>();
  readonly orientation = input<CredentialOrientation>('horizontal');
  readonly face = input<'front' | 'back'>('front');

  protected readonly initials = computed(() =>
    initialsOf(`${this.employee().first_name} ${this.employee().last_name}`),
  );

  protected readonly where = computed(() =>
    [this.employee().area?.name, this.employee().office?.name].filter(Boolean).join(' · '),
  );

  protected readonly temporary = computed(() => this.employee().employment_type === 'temporary');

  protected readonly expires = computed(() => this.format(this.employee().badge_expires_on));

  protected readonly dates = computed(() =>
    [
      this.format(this.employee().badge_issued_at) && `Emitida ${this.format(this.employee().badge_issued_at)}`,
      this.expires() && `Vigencia ${this.expires()}`,
    ]
      .filter(Boolean)
      .join(' · '),
  );

  /** dd/mm/yyyy; las fechas sin hora se leen tal cual para no correr un día. */
  private format(value: string | null | undefined): string {
    if (!value) {
      return '';
    }

    const [year, month, day] = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)?.slice(1) ?? [];
    if (year) {
      return `${day}/${month}/${year}`;
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return '';
    }

    const pad = (part: number) => String(part).padStart(2, '0');

    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;
  }
}
