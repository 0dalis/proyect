import { Component, computed, ElementRef, input, signal, viewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';

export interface InlineOption {
  value: string | number;
  label: string;
}

export type InlineFieldType = 'text' | 'email' | 'tel' | 'date' | 'number' | 'select' | 'pin';

/**
 * Dato con edición en su lugar: un lápiz pequeño junto a la etiqueta abre el
 * campo; Enter guarda, Escape cancela. El padre decide cómo guardar.
 *
 *   <app-inline-field label="Puesto" [value]="e.position" [save]="saveField('position')" />
 */
@Component({
  selector: 'app-inline-field',
  imports: [FormsModule],
  templateUrl: './inline-field.component.html',
  styleUrl: './inline-field.component.scss',
})
export class InlineFieldComponent {
  readonly label = input.required<string>();
  readonly value = input<string | number | null | undefined>(null);
  readonly type = input<InlineFieldType>('text');
  readonly options = input<InlineOption[]>([]);
  /** Texto a mostrar si difiere del valor (p. ej. nombre del área en vez del id). */
  readonly display = input<string | null>();
  readonly placeholder = input('');
  readonly editable = input(true);
  /** Guarda el nuevo valor; si lanza error, el campo sigue abierto. */
  readonly save = input<(value: string) => Promise<void>>();

  protected readonly editing = signal(false);
  protected readonly saving = signal(false);
  protected draft = '';

  private readonly field = viewChild<ElementRef<HTMLInputElement | HTMLSelectElement>>('field');

  protected readonly shown = computed(() => {
    const display = this.display();
    if (display) {
      return display;
    }
    const value = this.value();
    if (this.type() === 'pin') {
      return '••••';
    }
    if (this.type() === 'select') {
      return this.options().find((o) => String(o.value) === String(value))?.label ?? '—';
    }
    if (this.type() === 'date' && value) {
      return new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
      });
    }
    return value === null || value === undefined || value === '' ? '—' : String(value);
  });

  protected start(): void {
    const value = this.value();
    this.draft =
      this.type() === 'pin'
        ? ''
        : this.type() === 'date'
          ? String(value ?? '').slice(0, 10)
          : String(value ?? '');
    this.editing.set(true);
    setTimeout(() => this.field()?.nativeElement.focus());
  }

  protected cancel(): void {
    this.editing.set(false);
  }

  protected async commit(): Promise<void> {
    const save = this.save();
    if (!save || this.saving()) {
      return;
    }
    if (
      this.type() !== 'pin' &&
      this.draft === String(this.value() ?? '').slice(0, this.type() === 'date' ? 10 : undefined)
    ) {
      this.editing.set(false);
      return;
    }
    this.saving.set(true);
    try {
      await save(this.draft);
      this.editing.set(false);
    } catch {
      // El padre muestra el error; el campo queda abierto para corregir
      setTimeout(() => this.field()?.nativeElement.focus());
    } finally {
      this.saving.set(false);
    }
  }
}
