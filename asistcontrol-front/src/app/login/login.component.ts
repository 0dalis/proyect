import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { PublicServicesService } from '../services/public/public-services.service';

@Component({
  selector: 'app-login',
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {

  email = '';
  password = '';

  constructor(private publicservices: PublicServicesService, private router: Router) {}

  onLogin() {
    this.publicservices.login({
      email: this.email,
      password: this.password
    }).subscribe({
      next: () => {
        this.router.navigate(['/welcome']);
      },
      error: (err) => {
        console.error(err);
        alert('Credenciales incorrectas');
      }
    });
  }
}
