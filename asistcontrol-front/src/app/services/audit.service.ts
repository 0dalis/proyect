import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class AuditService {

  private BASE = `${environment.apiUrl}/web/audit`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  list(filters: Record<string, any> = {}): Observable<any> {
    let params = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params = params.set(key, String(value));
      }
    });
    return this.http.get(this.BASE, { ...this.httpOptions, params });
  }
}
