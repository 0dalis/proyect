import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { PayrollConcept, PayrollPeriod, PayrollSettings } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class PayrollService {

  private BASE = `${environment.apiUrl}/web/payroll`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  getSettings(): Observable<{ settings: PayrollSettings }> {
    return this.http.get<{ settings: PayrollSettings }>(`${this.BASE}/settings`, this.httpOptions);
  }

  updateSettings(data: Partial<PayrollSettings>): Observable<any> {
    return this.http.put(`${this.BASE}/settings`, data, this.httpOptions);
  }

  concepts(): Observable<{ concepts: PayrollConcept[] }> {
    return this.http.get<{ concepts: PayrollConcept[] }>(`${this.BASE}/concepts`, this.httpOptions);
  }

  createConcept(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/concepts`, data, this.httpOptions);
  }

  updateConcept(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/concepts/${id}`, data, this.httpOptions);
  }

  deleteConcept(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/concepts/${id}`, this.httpOptions);
  }

  periods(): Observable<{ periods: PayrollPeriod[] }> {
    return this.http.get<{ periods: PayrollPeriod[] }>(`${this.BASE}/periods`, this.httpOptions);
  }

  createPeriod(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/periods`, data, this.httpOptions);
  }

  getPeriod(id: number): Observable<{ period: PayrollPeriod }> {
    return this.http.get<{ period: PayrollPeriod }>(`${this.BASE}/periods/${id}`, this.httpOptions);
  }

  calculatePeriod(id: number): Observable<any> {
    return this.http.post(`${this.BASE}/periods/${id}/calculate`, {}, this.httpOptions);
  }

  closePeriod(id: number): Observable<any> {
    return this.http.post(`${this.BASE}/periods/${id}/close`, {}, this.httpOptions);
  }

  deletePeriod(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/periods/${id}`, this.httpOptions);
  }
}
