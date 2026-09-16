import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ProfileService {

  private BASE = `${environment.apiUrl}/web/profile`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  show(): Observable<any> {
    return this.http.get(this.BASE, this.httpOptions);
  }

  update(data: { email: string }): Observable<any> {
    return this.http.put(this.BASE, data, this.httpOptions);
  }

  updatePassword(data: { current_password: string; password: string; password_confirmation: string }): Observable<any> {
    return this.http.put(`${this.BASE}/password`, data, this.httpOptions);
  }
}
