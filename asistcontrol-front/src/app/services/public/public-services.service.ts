import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/operators';
import { ThemeService } from '../../theme/theme.service';
import { Router } from '@angular/router';


@Injectable({
  providedIn: 'root'
})
export class PublicServicesService {

  private API_URL = 'http://127.0.0.1:8000/api'; // ajusta tu URL

  constructor(private http: HttpClient, private themeService: ThemeService, private router: Router) {}

  login(data: { email: string, password: string }) {
    return this.http.post<any>(`${this.API_URL}/login`, data).pipe(
      tap(res => {
        localStorage.setItem('token', res.token);
        localStorage.setItem('user', JSON.stringify(res.user));
        //se llama a el servicio de temas
        this.themeService.initTheme(res.user);
      })
    );
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
