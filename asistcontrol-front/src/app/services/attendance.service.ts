import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Attendance, Paginated } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class AttendanceService {

  private BASE = `${environment.apiUrl}/web/attendance`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  list(filters: Record<string, any> = {}): Observable<Paginated<Attendance>> {
    let params = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params = params.set(key, String(value));
      }
    });
    return this.http.get<Paginated<Attendance>>(`${this.BASE}`, { ...this.httpOptions, params });
  }

  get(id: number): Observable<any> {
    return this.http.get(`${this.BASE}/${id}`, this.httpOptions);
  }

  correct(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/correct`, data, this.httpOptions);
  }

  kiosk(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/kiosk`, data, this.httpOptions);
  }

  summary(from: string, to: string): Observable<any> {
    const params = new HttpParams().set('from', from).set('to', to);
    return this.http.get(`${this.BASE}/summary`, { ...this.httpOptions, params });
  }
}
