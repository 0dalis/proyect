import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class CompanySetupService {

  private API_URL = environment.apiUrl;
  private BASE = `${this.API_URL}/web/company-setup`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  getStatus(): Observable<any> {
    return this.http.get(`${this.BASE}/status`, this.httpOptions);
  }

  getLimits(): Observable<any> {
    return this.http.get(`${this.BASE}/limits`, this.httpOptions);
  }

  updateProfile(data: any): Observable<any> {
    return this.http.put(`${this.BASE}/profile`, data, this.httpOptions);
  }

  getOffices(): Observable<any> {
    return this.http.get(`${this.BASE}/offices`, this.httpOptions);
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

  getAreas(): Observable<any> {
    return this.http.get(`${this.BASE}/areas`, this.httpOptions);
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

  getEmployees(): Observable<any> {
    return this.http.get(`${this.BASE}/employees`, this.httpOptions);
  }

  createEmployee(data: any): Observable<any> {
    return this.http.post(`${this.BASE}/employees`, data, this.httpOptions);
  }

  updateEmployee(id: number, data: any): Observable<any> {
    return this.http.put(`${this.BASE}/employees/${id}`, data, this.httpOptions);
  }

  deleteEmployee(id: number): Observable<any> {
    return this.http.delete(`${this.BASE}/employees/${id}`, this.httpOptions);
  }

  nextStep(step: number): Observable<any> {
    return this.http.post(`${this.BASE}/next-step`, { step }, this.httpOptions);
  }

  previousStep(step: number): Observable<any> {
    return this.http.post(`${this.BASE}/previous-step`, { step }, this.httpOptions);
  }

  completeSetup(): Observable<any> {
    return this.http.post(`${this.BASE}/complete`, {}, this.httpOptions);
  }
}
