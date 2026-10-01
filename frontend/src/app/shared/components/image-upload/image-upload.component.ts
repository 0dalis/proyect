import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { ToastService } from '../../../core/services/toast.service';

const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_SIZE = 2 * 1024 * 1024;

/** Iniciales (máximo 2 letras) que se muestran mientras no hay foto. */
export function initialsOf(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase();
}

/**
 * Subida de una imagen con vista previa: la foto de perfil, el logotipo de la
 * empresa y la foto de un empleado. Aquí solo se valida y se avisa; la subida
 * la hace quien lo usa (upload → archivo, removed → quitar) y actualiza
 * `photoUrl` con la respuesta del servidor.
 */
@Component({
  selector: 'app-image-upload',
  templateUrl: './image-upload.component.html',
  styleUrl: './image-upload.component.scss',
})
export class ImageUploadComponent {
  private readonly toast = inject(ToastService);

  /** Imagen actual (llega del servidor con ?v= para no servir la caché vieja). */
  readonly photoUrl = input<string | null>(null);
  readonly label = input('Foto');
  readonly hint = input('JPG, PNG o WebP · máximo 2 MB');
  /** Iniciales de respaldo mientras no hay foto. */
  readonly initials = input('');
  readonly busy = input(false);
  /** Oculta "Quitar" (por ejemplo, si solo el dueño toca el logotipo). */
  readonly canRemove = input(true);
  /**
   * Sin vista previa ni título: solo los botones. Para cuando la foto ya se
   * ve arriba (la ficha del empleado) y solo faltan los controles.
   */
  readonly compact = input(false);

  readonly upload = output<File>();
  readonly removed = output<void>();

  private readonly preview = signal<string | null>(null);

  protected readonly url = computed(() => this.preview() ?? this.photoUrl());

  constructor() {
    // Al terminar la subida (bien o mal) manda la imagen del servidor
    let wasBusy = false;
    effect(() => {
      if (wasBusy && !this.busy()) {
        this.dropPreview();
      }
      wasBusy = this.busy();
    });
  }

  protected pick(event: Event): void {
    const field = event.target as HTMLInputElement;
    const file = field.files?.[0] ?? null;
    field.value = '';

    if (!file) {
      return;
    }

    if (!ACCEPTED_TYPES.includes(file.type)) {
      this.toast.error('Usa una imagen JPG, PNG o WebP.', { title: 'Formato no válido' });
      return;
    }

    if (file.size > MAX_SIZE) {
      this.toast.error('La imagen no puede pesar más de 2 MB.', { title: 'Imagen muy pesada' });
      return;
    }

    this.dropPreview();
    this.preview.set(URL.createObjectURL(file));
    this.upload.emit(file);
  }

  protected remove(): void {
    this.dropPreview();
    this.removed.emit();
  }

  private dropPreview(): void {
    const url = this.preview();
    if (url) {
      URL.revokeObjectURL(url);
    }
    this.preview.set(null);
  }
}
