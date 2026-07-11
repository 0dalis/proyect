import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { PublicServicesService } from '../../services/public/public-services.service';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterLinkActive],
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.css'
})
export class SidebarComponent {
  @Input() isOpen = false;

  // Controladores de estado individuales y estáticos
  gestionExpanded = false;
  configExpanded = false;

  constructor(private auth: PublicServicesService) {}

  onLogout() {
    this.auth.logout();
  }
}
