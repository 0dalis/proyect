import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/operators';
import { ThemeService } from '../../theme/theme.service';
import { Router } from '@angular/router';



@Injectable({
  providedIn: 'root'
})
export class PublicServicesService {

  private API_URL = 'http://localhost:8000/api';

  constructor(private http: HttpClient, private themeService: ThemeService, private router: Router) {}

  login(data: { email: string, password: string }) {
    return this.http.post<any>(`${this.API_URL}/login`, data, {
      withCredentials: true // 👈 Si falta esto, el navegador jamás guardará la cookie
    });
  }

  getToken(): string | null {
    return localStorage.getItem('token');
  }

  getUser(): any {
    return JSON.parse(localStorage.getItem('user') || '{}');
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');

    this.router.navigate(['/Login']);
  }
}
