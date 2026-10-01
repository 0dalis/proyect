import { Component, input } from '@angular/core';

/**
 * Encabezado de cada pantalla: título, descripción y acciones a la derecha
 * (proyectadas).
 */
@Component({
  selector: 'app-page-header',
  templateUrl: './page-header.component.html',
  styleUrl: './page-header.component.scss',
})
export class PageHeaderComponent {
  readonly heading = input.required<string>();
  readonly description = input<string>();
}
