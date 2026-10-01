import { DatePipe } from '@angular/common';
import { Component, computed, DestroyRef, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive } from '@angular/router';
import { filter } from 'rxjs';
import { canAccess } from '../../core/guards/access.guard';
import { AuthService } from '../../core/services/auth.service';
import { LayoutService } from '../layout.service';
import { NavGroup, PANEL_NAVIGATION } from '../navigation';

const OPEN_KEY = 'asist.sidebar.open';

/**
 * Menú lateral. Cada grupo se despliega como cartas que bajan una tras otra
 * con un pequeño rebote (y se recogen en orden inverso). El grupo de la
 * página actual siempre se abre; los demás recuerdan cómo los dejaste.
 */
@Component({
  selector: 'app-sidebar',
  imports: [RouterLink, RouterLinkActive, DatePipe],
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.scss',
})
export class SidebarComponent {
  protected readonly auth = inject(AuthService);
  protected readonly layout = inject(LayoutService);
  private readonly router = inject(Router);

  protected readonly company = computed(() => this.auth.user()?.company ?? null);

  /** Marca del sidebar: el nombre de la empresa, con "AsistControl" de respaldo. */
  protected readonly companyName = computed(() => this.company()?.name?.trim() || 'AsistControl');

  /** Letra del nombre, mientras la empresa no sube su logotipo. */
  protected readonly companyInitial = computed(() => (this.companyName()[0] ?? 'A').toUpperCase());

  /** Solo los enlaces que el usuario puede abrir; grupos vacíos se ocultan. */
  protected readonly groups = computed(() => {
    if (!this.auth.user()) {
      return [];
    }
    return PANEL_NAVIGATION.map((group) => ({
      ...group,
      items: group.items.filter((item) => canAccess(this.auth, item.access)),
    })).filter((group) => group.items.length > 0);
  });

  protected readonly openGroups = signal<Set<string>>(this.readOpen());

  constructor() {
    this.openActiveGroup(this.router.url);
    const subscription = this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe((event) => this.openActiveGroup(event.urlAfterRedirects));
    inject(DestroyRef).onDestroy(() => subscription.unsubscribe());
  }

  protected isOpen(group: NavGroup): boolean {
    return this.openGroups().has(group.label);
  }

  protected toggle(group: NavGroup): void {
    const open = new Set(this.openGroups());
    open.has(group.label) ? open.delete(group.label) : open.add(group.label);
    this.setOpen(open);
  }

  /** Id del contenedor de cada grupo (para aria-controls). */
  protected groupId(group: NavGroup): string {
    return 'nav-' + group.label.normalize('NFD').replace(/[^a-zA-Z]/g, '').toLowerCase();
  }

  private openActiveGroup(url: string): void {
    const path = url.split('?')[0];
    const active = PANEL_NAVIGATION.find((group) =>
      group.items.some((item) =>
        item.path === '/panel' ? path === '/panel' : path === item.path || path.startsWith(item.path + '/'),
      ),
    );
    if (active && !this.openGroups().has(active.label)) {
      this.setOpen(new Set([...this.openGroups(), active.label]));
    }
  }

  private setOpen(open: Set<string>): void {
    this.openGroups.set(open);
    try {
      localStorage.setItem(OPEN_KEY, JSON.stringify([...open]));
    } catch {
      // Sin almacenamiento: se recuerda solo en esta pestaña
    }
  }

  private readOpen(): Set<string> {
    try {
      const saved = JSON.parse(localStorage.getItem(OPEN_KEY) ?? 'null');
      if (Array.isArray(saved)) {
        return new Set(saved.filter((label): label is string => typeof label === 'string'));
      }
    } catch {
      // valor dañado: se usa el predeterminado
    }
    // Primera vez: "Mi espacio" abierto
    return new Set([PANEL_NAVIGATION[0].label]);
  }
}
