import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
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
  errorMessage = '';

  constructor(
    private publicservices: PublicServicesService, 
    private router: Router
  ) {}

  onLogin() {
    this.errorMessage = '';
    this.publicservices.login({
      email: this.email,
      password: this.password
    }).subscribe({
      next: () => {
        localStorage.setItem('is_logged_in', 'true');
        this.router.navigate(['/welcome']);
      },
      error: (err: HttpErrorResponse) => {
        console.error(err);
        if (err.error && err.error.message) {
          this.errorMessage = err.error.message;
        } else {
          this.errorMessage = 'Ocurrió un error al intentar conectar con el servidor.';
        }
      }
    });
  }
}