import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { switchMap, tap } from 'rxjs/operators';
import { Observable } from 'rxjs';
import { ThemeService } from '../../theme/theme.service';
import { environment } from '../../../environments/environment';
import { Router } from '@angular/router';

@Injectable({
  providedIn: 'root'
})
export class PublicServicesService {

  private API_URL = environment.apiUrl;
  private BASE_URL = environment.apiUrl.replace(/\/api$/, '');

  private httpOptions = { withCredentials: true };

  constructor(
    private http: HttpClient,
    private themeService: ThemeService,
    private router: Router
  ) {}

  login(data: { email: string, password: string }): Observable<any> {
    return this.http.get(`${this.BASE_URL}/sanctum/csrf-cookie`, this.httpOptions).pipe(
      switchMap(() =>
        this.http.post<any>(`${this.API_URL}/login`, data, this.httpOptions)
      )
    );
  }

  logout(): Observable<any> {
    return this.http.post(`${this.API_URL}/web/logout`, {}, this.httpOptions).pipe(
      tap(() => {
        localStorage.removeItem('is_logged_in');
        localStorage.removeItem('company_inactive');
        localStorage.removeItem('last_activity');
        this.router.navigate(['/login']);
      })
    );
  }

  getUserPermissions(): Observable<any> {
    return this.http.get(`${this.API_URL}/web/user-permissions`, this.httpOptions);
  }
}
