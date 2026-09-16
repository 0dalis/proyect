import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { ExportFormat } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class ExportService {

  private BASE = `${environment.apiUrl}/web/exports`;
  private httpOptions = { withCredentials: true };

  constructor(private http: HttpClient) {}

  download(resource: string, format: ExportFormat, filters: Record<string, any> = {}): void {
    let params = new HttpParams().set('format', format);
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params = params.set(key, String(value));
      }
    });

    this.http.get(`${this.BASE}/${resource}`, {
      ...this.httpOptions,
      params,
      responseType: 'blob',
      observe: 'response',
    }).subscribe({
      next: (response: HttpResponse<Blob>) => {
        const blob = response.body as Blob;
        const filename = this.filenameFrom(response, resource, format);
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      },
      error: () => {
        // El interceptor ya notifica errores de sesión.
      }
    });
  }

  private filenameFrom(response: HttpResponse<Blob>, resource: string, format: ExportFormat): string {
    const disposition = response.headers.get('Content-Disposition') || '';
    const match = disposition.match(/filename="?([^";]+)"?/i);
    if (match && match[1]) {
      return match[1];
    }
    return `${resource}_${new Date().toISOString().slice(0, 10)}.${format}`;
  }

  downloadPayslip(itemId: number, employeeCode: string = ''): void {
    this.http.get(`${this.BASE}/payslip/${itemId}`, {
      ...this.httpOptions,
      responseType: 'blob',
      observe: 'response',
    }).subscribe({
      next: (response: HttpResponse<Blob>) => {
        const blob = response.body as Blob;
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `recibo_${employeeCode || itemId}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      },
      error: () => {}
    });
  }
}
