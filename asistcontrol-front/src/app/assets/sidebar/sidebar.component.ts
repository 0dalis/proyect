import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { PublicServicesService } from '../../services/public/public-services.service';

@Component({
  selector: 'app-sidebar',
  imports: [CommonModule, RouterLink, RouterLinkActive],
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.css'
})
export class SidebarComponent {
  menuItems = [
    { label: 'Dashboard', icon: 'dashboard', route: '/welcome/dashboard' },
    { label: 'Usuarios', icon: 'people', route: '/welcome/users' },
    { label: 'Configuración', icon: 'settings', route: '/welcome/settings' },
  ];
  constructor(private auth: PublicServicesService) {}

  onLogout() {
    this.auth.logout();
  }
}
