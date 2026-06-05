import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { switchMap, tap } from 'rxjs/operators';
import { Observable } from 'rxjs';
import { ThemeService } from '../../theme/theme.service';
import { environment } from '../../../environments/environment';
import { Router } from '@angular/router';

export interface User {
  id: number | string;
  name?: string;
  email?: string;
  role?: string;
}

@Injectable({
  providedIn: 'root'
})
export class PublicServicesService {

  private API_URL = environment.apiUrl;
  private BASE_URL = environment.apiUrl.replace(/\/api$/, '');

  constructor(private http: HttpClient, private themeService: ThemeService, private router: Router) {}

  login(data: { email: string, password: string }): Observable<any> {
    return this.http.get(`${this.BASE_URL}/sanctum/csrf-cookie`, { observe: 'response' }).pipe(
      switchMap(() =>
        this.http.post<any>(`${this.API_URL}/login`, data).pipe(
          tap(res => {
            if (res.user) {
              localStorage.setItem('user', JSON.stringify(res.user));
              localStorage.setItem('token', 'authenticated');
            }
          })
        )
      )
    );
  }

  getUserPermissions() {
    return this.http.get<any>(`${this.API_URL}/user-permissions`);
  }

  getToken(): string | null {
    return localStorage.getItem('token');
  }

  getUser(): User | null {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) as User : null;
  }

  logout() {
    return this.http.post(`${this.API_URL}/logout`, {}).pipe(
      tap(() => {
        localStorage.removeItem('user');
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');
        this.router.navigate(['/login']);
      })
    );
  }
}
