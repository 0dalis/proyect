import { inject, Injectable } from '@angular/core';
import { Title } from '@angular/platform-browser';
import { RouterStateSnapshot, TitleStrategy } from '@angular/router';
import { LayoutService } from './layout.service';

/**
 * Usa el `title` de cada ruta para la pestaña del navegador y para el
 * encabezado del menú superior.
 */
@Injectable({ providedIn: 'root' })
export class PageTitleStrategy extends TitleStrategy {
  private readonly title = inject(Title);
  private readonly layout = inject(LayoutService);

  override updateTitle(snapshot: RouterStateSnapshot): void {
    const pageTitle = this.buildTitle(snapshot) ?? '';
    this.layout.pageTitle.set(pageTitle);
    this.title.setTitle(
      pageTitle ? `${pageTitle} · AsistControl` : 'AsistControl · Control de asistencia',
    );
  }
}
