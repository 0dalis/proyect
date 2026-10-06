import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { AuthService } from './auth.service';

/** Condición agrupada a partir del weather_code WMO de Open-Meteo. */
export type WeatherKind =
  | 'clear-day'
  | 'clear-night'
  | 'cloudy'
  | 'fog'
  | 'rain'
  | 'storm'
  | 'snow';

export interface WeatherState {
  kind: WeatherKind;
  /** Etiqueta en español para mostrar (Despejado, Lluvia, ...). */
  label: string;
  temperature: number;
  /** "tu ubicación", nombre de la oficina o la ciudad de respaldo. */
  place: string;
  /** De dónde salió la ubicación (para ofrecer "usar mi ubicación"). */
  source: 'device' | 'office' | 'fallback';
  isDay: boolean;
  /** Ruta del fondo local, si existe (public/weather/*.png). */
  background: string;
}

interface OpenMeteoCurrent {
  temperature_2m: number;
  weather_code: number;
  is_day: number;
}

interface OpenMeteoResponse {
  current: OpenMeteoCurrent;
  timezone?: string;
}

const CACHE_KEY = 'ac-weather-cache-v2';
const POS_KEY = 'ac-weather-pos-v2';
const CACHE_MS = 30 * 60 * 1000;
/** Sin geolocalización ni oficina con coordenadas: CDMX como último recurso. */
const FALLBACK: WeatherPosition = { lat: 19.4326, lon: -99.1332, place: 'Ciudad de México', source: 'fallback' };

interface WeatherPosition {
  lat: number;
  lon: number;
  place: string;
  /** device: geolocalización del navegador; office: coordenadas de la oficina. */
  source: 'device' | 'office' | 'fallback';
}

/** weather_code WMO → condición agrupada (https://open-meteo.com/en/docs). */
export function mapWeatherCode(code: number, isDay: boolean): { kind: WeatherKind; label: string } {
  if (code === 0 || code === 1) {
    return isDay
      ? { kind: 'clear-day', label: 'Despejado' }
      : { kind: 'clear-night', label: 'Noche despejada' };
  }
  if (code === 2) return { kind: 'cloudy', label: 'Medio nublado' };
  if (code === 3) return { kind: 'cloudy', label: 'Nublado' };
  if (code === 45 || code === 48) return { kind: 'fog', label: 'Niebla' };
  if ((code >= 51 && code <= 67) || (code >= 80 && code <= 82)) {
    return { kind: 'rain', label: 'Lluvia' };
  }
  if (code >= 95) return { kind: 'storm', label: 'Tormenta' };
  if ((code >= 71 && code <= 77) || code === 85 || code === 86) {
    return { kind: 'snow', label: 'Nieve' };
  }
  return { kind: 'cloudy', label: 'Nublado' };
}

export function backgroundFor(kind: WeatherKind): string {
  return `/weather/${kind}.png`;
}

/**
 * Clima de la ubicación aproximada del usuario (estilo app Clima de iOS:
 * fondo ambiental según el clima). Usa Open-Meteo (gratis, sin API key),
 * geolocalización del navegador y la oficina como respaldo.
 */
@Injectable({ providedIn: 'root' })
export class WeatherService {
  private readonly http = inject(HttpClient);
  private readonly auth = inject(AuthService);
  private memory: { at: number; key: string; value: WeatherState } | null = null;

  async current(): Promise<WeatherState> {
    const pos = await this.position();
    const key = `${pos.lat.toFixed(2)},${pos.lon.toFixed(2)}`;
    const cached = this.readCache(key);
    if (cached) return cached;

    const url =
      `https://api.open-meteo.com/v1/forecast?latitude=${pos.lat.toFixed(4)}` +
      `&longitude=${pos.lon.toFixed(4)}&current=temperature_2m,weather_code,is_day&timezone=auto`;
    try {
      const res = await firstValueFrom(this.http.get<OpenMeteoResponse>(url));
      const isDay = (res.current?.is_day ?? 1) === 1;
      const { kind, label } = mapWeatherCode(res.current?.weather_code ?? 3, isDay);
      const state: WeatherState = {
        kind,
        label,
        temperature: Math.round(res.current?.temperature_2m ?? 0),
        place: pos.place,
        source: pos.source,
        isDay,
        background: backgroundFor(kind),
      };
      this.writeCache(key, state);
      return state;
    } catch {
      // Sin internet o API caída: estado neutro, no rompe la vista.
      return {
        kind: 'cloudy',
        label: 'Nublado',
        temperature: NaN,
        place: pos.place,
        source: pos.source,
        isDay: true,
        background: backgroundFor('cloudy'),
      };
    }
  }

  /** Botón "usar mi ubicación": olvida la posición y vuelve a pedir permiso. */
  async refresh(): Promise<WeatherState> {
    this.memory = null;
    try {
      localStorage.removeItem(CACHE_KEY);
      localStorage.removeItem(POS_KEY);
    } catch {
      /* almacenamiento no disponible */
    }
    return this.current();
  }

  /**
   * Empleado: siempre el clima de su oficina (no se le pide ubicación).
   * Dueño/admin/gerente: geolocalización del navegador; si la niega, la
   * primera oficina de la empresa con coordenadas; si no hay, CDMX.
   */
  private async position(): Promise<WeatherPosition> {
    const user = this.auth.user();
    const loc = user?.weather_location;
    const office: WeatherPosition | null = loc
      ? { lat: loc.latitude, lon: loc.longitude, place: loc.place, source: 'office' }
      : null;

    if (user?.role === 'employee' && office) return office;

    // Solo se recuerda una ubicación real del dispositivo, nunca el respaldo:
    // así si cambian las oficinas o el permiso, se vuelve a evaluar.
    try {
      const raw = localStorage.getItem(POS_KEY);
      if (raw) return JSON.parse(raw) as WeatherPosition;
    } catch {
      /* sin storage */
    }

    const geo = await this.geolocate().catch(() => null);
    if (geo) {
      try {
        localStorage.setItem(POS_KEY, JSON.stringify(geo));
      } catch {
        /* sin storage */
      }
      return geo;
    }
    return office ?? FALLBACK;
  }

  private geolocate(): Promise<WeatherPosition | null> {
    if (!('geolocation' in navigator)) return Promise.resolve(null);
    return new Promise((resolve) => {
      try {
        navigator.geolocation.getCurrentPosition(
          (p) =>
            // Precisión reducida a ~1km (ubicación aproximada, como iOS).
            resolve({
              lat: Math.round(p.coords.latitude * 100) / 100,
              lon: Math.round(p.coords.longitude * 100) / 100,
              place: 'tu ubicación',
              source: 'device',
            }),
          () => resolve(null),
          { timeout: 8000, maximumAge: 30 * 60 * 1000 },
        );
      } catch {
        resolve(null);
      }
    });
  }

  private readCache(key: string): WeatherState | null {
    if (this.memory?.key === key && Date.now() - this.memory.at < CACHE_MS) return this.memory.value;
    try {
      const raw = localStorage.getItem(CACHE_KEY);
      if (!raw) return null;
      const parsed = JSON.parse(raw) as { at: number; key: string; value: WeatherState };
      if (parsed.key === key && Date.now() - parsed.at < CACHE_MS) {
        this.memory = parsed;
        return parsed.value;
      }
    } catch {
      /* caché corrupta */
    }
    return null;
  }

  private writeCache(key: string, value: WeatherState): void {
    this.memory = { at: Date.now(), key, value };
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify(this.memory));
    } catch {
      /* sin storage */
    }
  }
}
