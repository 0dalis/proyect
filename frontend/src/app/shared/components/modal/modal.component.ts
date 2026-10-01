import { Component, HostListener, input, output } from '@angular/core';

/**
 * Diálogo de vidrio. El contenido va por proyección y los botones con el
 * atributo `modal-footer`:
 *
 *   <app-modal heading="Nuevo turno" (closed)="close()">
 *     ...formulario...
 *     <div modal-footer>
 *       <button class="btn">Cancelar</button>
 *     </div>
 *   </app-modal>
 */
@Component({
  selector: 'app-modal',
  templateUrl: './modal.component.html',
  styleUrl: './modal.component.scss',
})
export class ModalComponent {
  readonly heading = input.required<string>();
  readonly size = input<'md' | 'lg' | 'xl'>('md');

  readonly closed = output<void>();

  @HostListener('document:keydown.escape')
  protected close(): void {
    this.closed.emit();
  }
}
