import { Component, ElementRef, inject, input, output, signal, ViewChild } from '@angular/core';
import { CredentialCompany, CredentialEmployee, CredentialOrientation } from '../../../core/models';
import { EmployeeService } from '../../../core/services/employee.service';
import { ProcessingService } from '../../../core/services/processing.service';
import { ToastService } from '../../../core/services/toast.service';
import { errorMessage } from '../../../core/utils/error-message';
import { CredentialCardComponent } from '../credential-card/credential-card.component';
import { ModalComponent } from '../modal/modal.component';

const ORIENTATION_KEY = 'asist.credencial.orientacion';

/** Orientación recordada: la comparten el preview y la impresión masiva. */
export function readCredentialOrientation(): CredentialOrientation {
  try {
    return localStorage.getItem(ORIENTATION_KEY) === 'vertical' ? 'vertical' : 'horizontal';
  } catch {
    return 'horizontal';
  }
}

export function saveCredentialOrientation(value: CredentialOrientation): void {
  try {
    localStorage.setItem(ORIENTATION_KEY, value);
  } catch {
    // Sin almacenamiento privado se queda en la sesión actual.
  }
}

/**
 * Preview de la credencial (frente y reverso) con la orientación que se va a
 * imprimir. La descarga captura estas dos caras con html2canvas y el servidor
 * las pega en el PDF; la impresión masiva de la lista usa el PDF del servidor.
 */
@Component({
  selector: 'app-credential-viewer',
  imports: [ModalComponent, CredentialCardComponent],
  templateUrl: './credential-viewer.component.html',
  styleUrl: './credential-viewer.component.scss',
})
export class CredentialViewerComponent {
  private readonly employeeService = inject(EmployeeService);
  private readonly toast = inject(ToastService);
  private readonly processing = inject(ProcessingService);

  readonly employee = input.required<CredentialEmployee>();
  readonly company = input.required<CredentialCompany>();
  readonly closed = output<void>();

  protected readonly orientation = signal<CredentialOrientation>(readCredentialOrientation());
  protected readonly busy = signal(false);

  @ViewChild('frontCard') private frontCard?: ElementRef<HTMLElement>;
  @ViewChild('backCard') private backCard?: ElementRef<HTMLElement>;

  protected setOrientation(value: CredentialOrientation): void {
    this.orientation.set(value);
    saveCredentialOrientation(value);
  }

  protected download(): Promise<void> {
    return this.render('download');
  }

  protected print(): Promise<void> {
    return this.render('print');
  }

  private async render(mode: 'download' | 'print'): Promise<void> {
    const employee = this.employee();

    if (this.busy()) {
      return;
    }

    // Se abre antes del primer await para no ser bloqueado por el navegador.
    const tab = mode === 'print' ? window.open('', '_blank') : null;
    this.busy.set(true);

    try {
      // La foto de las dos caras tarda: "Procesando…" desde antes de pedir el PDF
      await this.processing.run(async () => {
        const captures = await this.capture();
        const filename = `credencial-${employee.employee_code}.pdf`;

        if (captures) {
          const blob = await this.employeeService.renderBadge(
            employee,
            captures,
            this.orientation(),
          );

          mode === 'print' ? this.showIn(tab, blob, filename) : save(blob, filename);
        } else {
          // Sin canvas (o sin navegador compatible): el PDF del servidor.
          tab?.close();
          await this.employeeService.downloadBadge(employee, this.orientation());
        }
      });

      this.toast.success('Lista para imprimir.', {
        title: mode === 'print' ? 'Credencial enviada a impresión' : 'Credencial descargada',
        icon: 'person-badge',
      });
    } catch (error) {
      tab?.close();
      this.toast.error(errorMessage(error));
    } finally {
      this.busy.set(false);
    }
  }

  /** Foto de las dos caras tal como se ven, para pegarlas en el PDF. */
  private async capture(): Promise<{ front: string; back: string } | null> {
    const front = this.frontCard?.nativeElement;
    const back = this.backCard?.nativeElement;

    if (!front || !back) {
      return null;
    }

    try {
      // Se carga solo al imprimir, no en el bundle inicial.
      const { default: html2canvas } = await import('html2canvas');
      const options = { backgroundColor: '#ffffff', scale: 3, logging: false };

      return {
        front: (await html2canvas(front, options)).toDataURL('image/png'),
        back: (await html2canvas(back, options)).toDataURL('image/png'),
      };
    } catch {
      return null;
    }
  }

  private showIn(tab: Window | null, blob: Blob, filename: string): void {
    if (!tab) {
      // Ventana bloqueada: mejor una descarga que nada.
      save(blob, filename);

      return;
    }

    const url = URL.createObjectURL(blob);
    tab.location.href = url;
    setTimeout(() => URL.revokeObjectURL(url), 60_000);
  }
}

function save(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}
