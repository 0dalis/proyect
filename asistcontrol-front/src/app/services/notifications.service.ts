import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AppNotification } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class NotificationsService {

  private BASE = `${environment.apiUrl}/web/notifications`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  list(): Observable<{ notifications: AppNotification[] }> {
    return this.http.get<{ notifications: AppNotification[] }>(this.BASE, this.httpOptions);
  }

  recipients(): Observable<{ recipients: any[]; summary: any }> {
    return this.http.get<{ recipients: any[]; summary: any }>(`${this.BASE}/recipients`, this.httpOptions);
  }

  preview(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/preview`, data, this.httpOptions);
  }

  create(data: any): Observable<any> {
    return this.http.post(this.BASE, data, this.httpOptions);
  }

  update(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/${id}`, data, this.httpOptions);
  }

  remove(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/${id}`, this.httpOptions);
  }

  send(id: number): Observable<any> {
    return this.http.post(`${this.BASE}/${id}/send`, {}, this.httpOptions);
  }
}
