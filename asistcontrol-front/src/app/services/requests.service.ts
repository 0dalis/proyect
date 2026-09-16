import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { WorkRequest } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class RequestsService {

  private BASE = `${environment.apiUrl}/web/requests`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  list(filters: Record<string, any> = {}): Observable<{ requests: WorkRequest[] }> {
    let params = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params = params.set(key, String(value));
      }
    });
    return this.http.get<{ requests: WorkRequest[] }>(this.BASE, { ...this.httpOptions, params });
  }

  create(data: any): Observable<any> {
    return this.http.post(this.BASE, data, this.httpOptions);
  }

  update(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/${id}`, data, this.httpOptions);
  }

  approve(id: number): Observable<any> {
    return this.http.post(`${this.BASE}/${id}/approve`, {}, this.httpOptions);
  }

  reject(id: number): Observable<any> {
    return this.http.post(`${this.BASE}/${id}/reject`, {}, this.httpOptions);
  }

  balances(year: number): Observable<any> {
    const params = new HttpParams().set('year', String(year));
    return this.http.get(`${environment.apiUrl}/web/vacations/balances`, { ...this.httpOptions, params });
  }

  updateBalance(employeeId: number, data: { year: number; days_entitled: number; days_used?: number }): Observable<any> {
    return this.http.put(`${environment.apiUrl}/web/vacations/balances/${employeeId}`, data, this.httpOptions);
  }

  calendar(from: string, to: string, employeeId?: number | null): Observable<any> {
    let params = new HttpParams().set('from', from).set('to', to);
    if (employeeId) params = params.set('employee_id', String(employeeId));
    return this.http.get(`${environment.apiUrl}/web/vacations/calendar`, { ...this.httpOptions, params });
  }
}
