import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class HolidaysService {

  private BASE = `${environment.apiUrl}/web/holidays`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  list(): Observable<{ holidays: any[] }> {
    return this.http.get<{ holidays: any[] }>(this.BASE, this.httpOptions);
  }

  create(data: any): Observable<any> {
    return this.http.post(this.BASE, data, this.httpOptions);
  }

  remove(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/${id}`, this.httpOptions);
  }
}
