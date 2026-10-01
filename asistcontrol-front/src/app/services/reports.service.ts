import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ReportsService {

  private BASE = `${environment.apiUrl}/web/reports`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  attendance(params: Record<string, any>): Observable<any> {
    return this.http.get(`${this.BASE}/attendance`, { ...this.httpOptions, params: this.toParams(params) });
  }

  employeeReport(id: number, from: string, to: string): Observable<any> {
    const params = new HttpParams().set('from', from).set('to', to);
    return this.http.get(`${this.BASE}/attendance/employee/${id}`, { ...this.httpOptions, params });
  }

  payroll(periodId: number, groupBy: string = 'company'): Observable<any> {
    const params = new HttpParams().set('period_id', String(periodId)).set('group_by', groupBy);
    return this.http.get(`${this.BASE}/payroll`, { ...this.httpOptions, params });
  }

  private toParams(filters: Record<string, any>): HttpParams {
    let params = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params = params.set(key, String(value));
      }
    });
    return params;
  }
}
