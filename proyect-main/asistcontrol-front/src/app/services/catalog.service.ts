import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Area, Office } from '../models/api.models';

/**
 * Catálogos de oficinas y áreas (reutiliza los endpoints del wizard de setup,
 * que ya exponen CRUD completo para la empresa autenticada).
 */
@Injectable({ providedIn: 'root' })
export class CatalogService {

  private BASE = `${environment.apiUrl}/web/company-setup`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  offices(): Observable<{ offices: Office[] }> {
    return this.http.get<{ offices: Office[] }>(`${this.BASE}/offices`, this.httpOptions);
  }

  createOffice(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/offices`, data, this.httpOptions);
  }

  updateOffice(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/offices/${id}`, data, this.httpOptions);
  }

  deleteOffice(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/offices/${id}`, this.httpOptions);
  }

  areas(): Observable<{ areas: Area[] }> {
    return this.http.get<{ areas: Area[] }>(`${this.BASE}/areas`, this.httpOptions);
  }

  createArea(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/areas`, data, this.httpOptions);
  }

  updateArea(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/areas/${id}`, data, this.httpOptions);
  }

  deleteArea(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/areas/${id}`, this.httpOptions);
  }
}
