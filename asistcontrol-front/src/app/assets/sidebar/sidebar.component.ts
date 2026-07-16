import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive } from '@angular/router';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterLinkActive],
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.css'
})
export class SidebarComponent {
  isOpen = false;
  gestionExpanded = false;
  configExpanded = false;

  // NUEVA PROPIEDAD: Control del menú del perfil superior
  userMenuOpen = false;

  isSidebarHovered = false;
  private hoverTimeout: any;

  onSidebarMouseEnter() {
    if (this.hoverTimeout) {
      clearTimeout(this.hoverTimeout);
    }
    this.isSidebarHovered = true;
  }

  onSidebarMouseLeave() {
    this.hoverTimeout = setTimeout(() => {
      this.isSidebarHovered = false;
      this.gestionExpanded = false;
      this.configExpanded = false;
    }, 500);
  }

  onLogout() {
    console.log('Cerrar sesión');
  }
}
