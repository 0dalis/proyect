import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { PublicServicesService } from '../services/public/public-services.service';

import Toastify from 'toastify-js';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {

  email = '';
  password = '';

  isLoading = false;

  constructor(
    private publicservices: PublicServicesService,
    private router: Router
  ) {}

  get formValid(): boolean {
    return (
      this.email.trim() !== '' &&
      this.password.trim() !== ''
    );
  }

  private showSuccess(message: string) {
    Toastify({
      text: message,
      duration: 3000,
      gravity: "top",
      position: "right",
      close: true,
      stopOnFocus: true,
      style: {
        background: "#16a34a"
      }
    }).showToast();
  }

  private showError(message: string) {
    Toastify({
      text: message,
      duration: 4000,
      gravity: "top",
      position: "right",
      close: true,
      stopOnFocus: true,
      style: {
        background: "#dc2626"
      }
    }).showToast();
  }

  onLogin() {

    if (!this.formValid || this.isLoading) {
      return;
    }

    this.isLoading = true;

    this.publicservices.login({
      email: this.email,
      password: this.password
    }).subscribe({

      next: (response: any) => {

        localStorage.setItem('is_logged_in', 'true');

        const fullName =
          `${response.user.first_name} ${response.user.last_name}`;

        this.showSuccess(`Bienvenido ${fullName}`);

        this.router.navigate(['/welcome']);

      },

      error: (err: HttpErrorResponse) => {

        if (err.error?.message) {
          this.showError(err.error.message);
        } else {
          this.showError('Ocurrió un error al conectar con el servidor.');
        }

        this.isLoading = false;
      },

      complete: () => {
        this.isLoading = false;
      }

    });

  }

}
