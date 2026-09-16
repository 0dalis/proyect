import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Employee, EmployeeCompensation } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class EmployeesService {

  private BASE = `${environment.apiUrl}/web/employees`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  list(filters: Record<string, any> = {}): Observable<{ employees: Employee[] }> {
    let params = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params = params.set(key, String(value));
      }
    });
    return this.http.get<{ employees: Employee[] }>(this.BASE, { ...this.httpOptions, params });
  }

  get(id: number): Observable<{ employee: Employee }> {
    return this.http.get<{ employee: Employee }>(`${this.BASE}/${id}`, this.httpOptions);
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

  generateCode(): Observable<{ code: string }> {
    return this.http.post<{ code: string }>(`${this.BASE}/generate-code`, {}, this.httpOptions);
  }

  updateCompensation(id: number, data: Partial<EmployeeCompensation>): Observable<any> {
    return this.http.put(`${this.BASE}/${id}/compensation`, data, this.httpOptions);
  }

  syncConcepts(id: number, concepts: any[]): Observable<any> {
    return this.http.put(`${this.BASE}/${id}/concepts`, { concepts }, this.httpOptions);
  }

  // ----- Credencial / QR -----
  credential(id: number): Observable<any> {
    return this.http.get(`${this.BASE}/${id}/credential`, this.httpOptions);
  }

  updateCredential(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/${id}/credential`, data, this.httpOptions);
  }

  regenerateQr(id: number): Observable<any> {
    return this.http.post(`${this.BASE}/${id}/credential/qr`, {}, this.httpOptions);
  }

  uploadCredentialPdf(id: number, blob: Blob): Observable<any> {
    const formData = new FormData();
    formData.append('pdf', blob, `credencial_${id}.pdf`);
    return this.http.post(`${this.BASE}/${id}/credential/pdf`, formData, this.httpOptions);
  }

  uploadPhoto(id: number, file: File): Observable<any> {
    const formData = new FormData();
    formData.append('photo', file, file.name);
    return this.http.post(`${this.BASE}/${id}/photo`, formData, this.httpOptions);
  }

  removePhoto(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/${id}/photo`, this.httpOptions);
  }
}
