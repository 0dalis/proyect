import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';

export type QueryParams = Record<string, string | number | boolean | null | undefined>;

/**
 * Acceso HTTP a la API de Laravel. Devuelve promesas y descarta los
 * parámetros vacíos. Los servicios de cada dominio lo usan.
 */
@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);

  readonly baseUrl = environment.apiUrl;

  get<T>(path: string, params: QueryParams = {}, headers?: HttpHeaders): Promise<T> {
    return firstValueFrom(
      this.http.get<T>(this.url(path), { params: this.params(params), headers }),
    );
  }

  post<T>(path: string, body: unknown = {}, headers?: HttpHeaders): Promise<T> {
    return firstValueFrom(this.http.post<T>(this.url(path), body, { headers }));
  }

  put<T>(path: string, body: unknown = {}): Promise<T> {
    return firstValueFrom(this.http.put<T>(this.url(path), body));
  }

  patch<T>(path: string, body: unknown = {}): Promise<T> {
    return firstValueFrom(this.http.patch<T>(this.url(path), body));
  }

  delete<T>(path: string): Promise<T> {
    return firstValueFrom(this.http.delete<T>(this.url(path)));
  }

  /**
   * Descarga un archivo (PDF, CSV) y lo guarda con el nombre indicado.
   */
  async download(
    path: string,
    filename: string,
    params: QueryParams = {},
    body?: unknown,
  ): Promise<void> {
    const request = body
      ? this.blob(path, body)
      : firstValueFrom(this.http.get(this.url(path), { responseType: 'blob', params: this.params(params) }));
    const blob = await request;
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
  }

  /** Archivo del servidor sin descargarlo, para abrirlo en otra pestaña. */
  async blob(path: string, body: unknown): Promise<Blob> {
    return firstValueFrom(this.http.post(this.url(path), body, { responseType: 'blob' }));
  }

  /**
   * Pide a Laravel la cookie XSRF-TOKEN. HttpClient la copia en la cabecera
   * X-XSRF-TOKEN de cada POST/PUT/PATCH/DELETE (protección CSRF).
   */
  async csrfCookie(): Promise<void> {
    await firstValueFrom(this.http.get(environment.csrfCookieUrl, { responseType: 'text' }));
  }

  isApiUrl(url: string): boolean {
    return url.startsWith(this.baseUrl);
  }

  private url(path: string): string {
    return `${this.baseUrl}/${path}`;
  }

  private params(params: QueryParams): HttpParams {
    let result = new HttpParams();
    for (const [key, value] of Object.entries(params)) {
      if (value !== null && value !== undefined && value !== '') {
        result = result.set(key, String(value));
      }
    }
    return result;
  }
}
