import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class LoansService {

  private BASE = `${environment.apiUrl}/web`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  list(employeeId: number): Observable<{ loans: any[] }> {
    return this.http.get<{ loans: any[] }>(`${this.BASE}/employees/${employeeId}/loans`, this.httpOptions);
  }

  create(employeeId: number, data: any): Observable<any> {
    return this.http.post(`${this.BASE}/employees/${employeeId}/loans`, data, this.httpOptions);
  }

  update(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/loans/${id}`, data, this.httpOptions);
  }

  remove(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/loans/${id}`, this.httpOptions);
  }
}
