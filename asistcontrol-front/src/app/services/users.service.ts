import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class UsersService {

  private BASE = `${environment.apiUrl}/web/users`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  list(): Observable<{ users: any[] }> {
    return this.http.get<{ users: any[] }>(this.BASE, this.httpOptions);
  }

  roles(): Observable<{ roles: { name: string; label: string }[] }> {
    return this.http.get<{ roles: { name: string; label: string }[] }>(`${this.BASE}/roles`, this.httpOptions);
  }

  create(data: any): Observable<any> {
    return this.http.post(this.BASE, data, this.httpOptions);
  }

  update(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/${id}`, data, this.httpOptions);
  }

  resetPassword(id: number, password: string): Observable<any> {
    return this.http.post(`${this.BASE}/${id}/reset-password`, { password }, this.httpOptions);
  }

  remove(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/${id}`, this.httpOptions);
  }
}
