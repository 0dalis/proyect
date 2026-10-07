import { Component, computed, inject, input, OnInit, signal } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { WeatherService, WeatherState } from '../../../core/services/weather.service';

const KIND_ICONS: Record<string, string> = {
  'clear-day': 'bi-sun-fill',
  'clear-night': 'bi-moon-stars-fill',
  cloudy: 'bi-clouds-fill',
  fog: 'bi-cloud-fog2-fill',
  rain: 'bi-cloud-rain-heavy-fill',
  storm: 'bi-cloud-lightning-rain-fill',
  snow: 'bi-snow',
};

/**
 * Encabezado del panel estilo app Clima de iOS: mismo título y descripción
 * del PageHeader, pero sobre un fondo ambiental según el clima real de la
 * ubicación aproximada del usuario (Open-Meteo + geolocalización).
 * Si el clima no carga, se muestra el degradado neutro sin romper la vista.
 *
 *   <app-weather-hero heading="Hola, Ana" description="...">
 *     <a routerLink="...">acción</a>
 *   </app-weather-hero>
 */
@Component({
  selector: 'app-weather-hero',
  templateUrl: './weather-hero.component.html',
  styleUrl: './weather-hero.component.scss',
})
export class WeatherHeroComponent implements OnInit {
  readonly heading = input.required<string>();
  readonly description = input<string>();

  private readonly weatherService = inject(WeatherService);
  private readonly auth = inject(AuthService);

  protected readonly weather = signal<WeatherState | null>(null);
  protected readonly loading = signal(true);
  /** El video ya puede reproducirse: aparece con un fundido sobre la imagen. */
  protected readonly videoReady = signal(false);
  /**
   * Se usa la oficina o CDMX porque no hubo geolocalización: se ofrece
   * "usar mi ubicación". Al empleado no, él siempre ve el de su oficina.
   */
  protected readonly notice = computed(
    () => !!this.weather() && this.weather()!.source !== 'device' && this.auth.user()?.role !== 'employee',
  );

  protected readonly icon = computed(() =>
    this.weather() ? (KIND_ICONS[this.weather()!.kind] ?? 'bi-cloud-fill') : 'bi-cloud-fill',
  );
  protected readonly kind = computed(() => this.weather()?.kind ?? 'default');

  protected hasTemp(w: WeatherState): boolean {
    return Number.isFinite(w.temperature);
  }

  async ngOnInit(): Promise<void> {
    try {
      const state = await this.weatherService.current();
      this.weather.set(state);
    } catch {
      this.weather.set(null);
    } finally {
      this.loading.set(false);
    }
  }

  protected async retry(): Promise<void> {
    this.loading.set(true);
    this.videoReady.set(false);
    try {
      const state = await this.weatherService.refresh();
      this.weather.set(state);
    } catch {
      this.weather.set(null);
    } finally {
      this.loading.set(false);
    }
  }
}
