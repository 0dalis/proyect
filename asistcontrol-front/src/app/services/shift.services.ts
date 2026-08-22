import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ShiftService {

  private API_URL = environment.apiUrl;
  private BASE = `${this.API_URL}/web/shifts`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  getShifts(): Observable<any> {
    return this.http.get(this.BASE, this.httpOptions);
  }

  createShift(data: any): Observable<any> {
    return this.http.post(this.BASE, data, this.httpOptions);
  }

  updateShift(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/${id}`, data, this.httpOptions);
  }

  deleteShift(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/${id}`, this.httpOptions);
  }

  assignShift(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/assign`, data, this.httpOptions);
  }
}
