import { Injectable, signal } from '@angular/core';

/**
 * Estado compartido entre el menú superior y el lateral.
 */
@Injectable({ providedIn: 'root' })
export class LayoutService {
  readonly sidebarOpen = signal(false);
  readonly pageTitle = signal('');

  toggleSidebar(): void {
    this.sidebarOpen.update((open) => !open);
  }

  closeSidebar(): void {
    this.sidebarOpen.set(false);
  }
}
