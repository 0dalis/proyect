import { Component, Input, Output, EventEmitter, OnInit, AfterViewInit, OnDestroy, ElementRef, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { CompleteProfileService } from '../../services/public/completeprofile.services';
import * as Highcharts from 'highcharts/highmaps';
import mexicoMap from '@highcharts/map-collection/countries/mx/mx-all.topo.json';
import * as L from 'leaflet';
import tzlookup from 'tz-lookup';

import Toastify from 'toastify-js';

const STATE_NAME_OVERRIDES: Record<string, string> = {
  'Distrito Federal': 'CIUDAD DE MEXICO',
  'Veracruz': 'VERACRUZ DE IGNACIO DE LA LLAVE',
  'Michoacán': 'MICHOACAN DE OCAMPO',
  'Coahuila': 'COAHUILA DE ZARAGOZA',
};

const RADIUS_MIN = 10;
const RADIUS_MAX = 600;
const RADIUS_DEFAULT = 100;

const MAIN_PIN_SVG = (color: string) => `
  <svg width="30" height="42" viewBox="0 0 30 42" xmlns="http://www.w3.org/2000/svg">
    <path d="M15 0C6.7 0 0 6.7 0 15c0 11.2 15 27 15 27s15-15.8 15-27C30 6.7 23.3 0 15 0z" fill="${color}"/>
    <circle cx="15" cy="15" r="6" fill="#fff"/>
  </svg>`;

const RADIUS_PIN_SVG = `
  <svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg">
    <circle cx="13" cy="13" r="10" fill="#2563eb" stroke="#fff" stroke-width="2.5"/>
    <circle cx="13" cy="13" r="4" fill="#fff"/>
  </svg>`;

function normalize(s: string): string {
  return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().trim();
}

@Component({
  selector: 'app-step2-offices',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './step2-offices.component.html',
  styleUrl: './step2-offices.component.css'
})
export class Step2OfficesComponent implements OnInit, AfterViewInit, OnDestroy {

  @Input() limits: any = null;
  @Output() next = new EventEmitter<void>();
  @Output() back = new EventEmitter<void>();

  @ViewChild('stateMapEl') stateMapEl!: ElementRef<HTMLDivElement>;
  @ViewChild('officeMapEl') officeMapEl!: ElementRef<HTMLDivElement>;

  officeLimitData: any = null;

  offices: any[] = [];
  isLoading = true;
  showForm = false;
  editingId: number | null = null;
  form = { name: '', code: '', latitude: 0, longitude: 0, radius_meters: RADIUS_DEFAULT, timezone: 'UTC', country: '' };
  isSubmitting = false;
  errors: any = {};

  formStage: 'map' | 'estado' | 'leaflet' = 'map';

  isInternational = false;
  userLocation: { lat: number; lng: number } | null = null;
  userCountry: string | null = null;
  private isInternationalPinMoved = false;
  private countryForCoords: { lat: number; lng: number } | null = null;
  private countryDebounce: any = null;

  mexicoData: any[] | null = null;
  selectedState: any = null;
  selectedMunicipio: any = null;
  selectedMunicipioName = '';

  searchQuery = '';
  searchResults: any[] = [];
  private searchAbort: AbortController | null = null;

  radiusDisplay = RADIUS_DEFAULT;
  private currentRadius = RADIUS_DEFAULT;
  private currentBearing = 0;

  private chart: Highcharts.Chart | null = null;
  private officeMap: L.Map | null = null;
  private mainPin: L.Marker | null = null;
  private radiusPin: L.Marker | null = null;
  private radiusCircle: L.Circle | null = null;

  constructor(private completeProfileService: CompleteProfileService, private http: HttpClient) {}

  get officeLimit(): number | null {
    return this.officeLimitData?.office_limit ?? this.limits?.office_limit ?? null;
  }

  get canCreate(): boolean {
    return this.officeLimitData?.can_create ?? this.limits?.can_create_office ?? true;
  }

  get availableOffices(): number | null {
    return this.officeLimitData?.available ?? null;
  }

  get overLimitMessage(): string {
    const limit = this.officeLimit;
    if (limit === null) return '';
    return `Tu plan permite hasta ${limit} oficina${limit === 1 ? '' : 's'}. Las que excedan el límite solo pueden eliminarse.`;
  }

  get selectedStateName(): string | null {
    return this.selectedState ? this.selectedState.estado : null;
  }

  get municipios(): any[] {
    return this.selectedState?.municipios ?? [];
  }

  isLocked(office: any): boolean {
    const limit = this.officeLimit;
    if (limit === null) return false;
    const allowedIds = [...this.offices]
      .sort((a, b) => a.id - b.id)
      .slice(0, limit)
      .map((o) => o.id);
    return !allowedIds.includes(office.id);
  }

  ngOnInit(): void {
    this.loadOffices();
    this.loadOfficeLimit();
  }

  ngAfterViewInit(): void {
    // el mapa de estados se inicializa al entrar a la etapa 'map'
  }

  ngOnDestroy(): void {
    if (this.searchAbort) this.searchAbort.abort();
    if (this.countryDebounce) clearTimeout(this.countryDebounce);
    this.destroyOfficeMap();
  }

  loadOfficeLimit(): void {
    this.completeProfileService.getOfficeLimit().subscribe({
      next: (res: any) => {
        this.officeLimitData = res;
      },
      error: () => {}
    });
  }

  loadOffices(): void {
    this.isLoading = true;
    this.completeProfileService.getOffices().subscribe({
      next: (res: any) => {
        this.offices = res.offices;
        this.isLoading = false;
      },
      error: () => {
        this.isLoading = false;
        this.showError('Error al cargar oficinas.');
      }
    });
  }

  private async loadMexicoData(): Promise<any[]> {
    const cached = this.mexicoData;
    if (cached) return cached;
    try {
      const res: any = await firstValueFrom(this.http.get('recursos/mexico/mapa_mexico.json'));
      this.mexicoData = res ?? [];
    } catch {
      this.mexicoData = [];
      this.showError('Error al cargar los municipios.');
    }
    return this.mexicoData ?? [];
  }

  // ---------- Highcharts: mapa de México ----------

  private initMap(attempt = 0): void {
    if (!this.stateMapEl) {
      if (attempt < 20) {
        setTimeout(() => this.initMap(attempt + 1), 100);
      }
      return;
    }

    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }

    try {
      const data: [string, number][] = mexicoMap.objects.default.geometries.map((geo: any) => [
        geo.properties['hc-key'],
        0
      ]);

      this.chart = Highcharts.mapChart(this.stateMapEl.nativeElement as HTMLElement, {
        chart: {
          map: mexicoMap as any
        },
        title: {
          text: 'Mapa de México — Selecciona un estado'
        },
        subtitle: {
          text: 'Haz clic en el estado donde se ubicará tu oficina'
        },
        accessibility: {
          enabled: false
        },
        colorAxis: {
          min: 0,
          max: 100,
          visible: false
        },
        tooltip: {
          pointFormat: '<b>{point.name}</b>'
        },
        plotOptions: {
          series: {
            point: {
              events: {
                click: (event: any) => {
                  const stateName = event.point.name;
                  this.onStateClick(stateName);
                }
              }
            }
          }
        },
        series: [{
          type: 'map',
          name: 'Estados',
          data,
          joinBy: 'hc-key',
          states: {
            hover: {
              color: '#ea580c',
              borderColor: '#ffffff',
              borderWidth: 1
            }
          },
          dataLabels: {
            enabled: false
          }
        }]
      } as any);

      this.selectedState = null;
      this.selectedMunicipio = null;
    } catch (e) {
      console.error('Error al cargar el mapa de México:', e);
      this.showError('Error al cargar el mapa de México.');
    }
  }

  async onStateClick(name: string): Promise<void> {
    if (!name) return;
    const data = await this.loadMexicoData();
    if (data.length === 0) return;

    const estado = STATE_NAME_OVERRIDES[name] ??
      data.find((s: any) => normalize(s.estado) === normalize(name))?.estado;

    if (!estado) {
      this.showError('Estado no encontrado en los datos.');
      return;
    }

    this.selectedState = data.find((s: any) => s.estado === estado);
    this.selectedMunicipio = null;
    this.selectedMunicipioName = '';
    this.formStage = 'estado';
  }

  // ---------- Navegación entre etapas del formulario ----------

  openCreate(): void {
    this.editingId = null;
    this.form = { name: '', code: '', latitude: 0, longitude: 0, radius_meters: RADIUS_DEFAULT, timezone: 'UTC', country: '' };
    this.errors = {};
    this.countryForCoords = null;
    if (this.countryDebounce) clearTimeout(this.countryDebounce);
    this.countryDebounce = null;
    this.showForm = true;
    this.enterMapStage();
    this.requestUserLocation();
  }

  private requestUserLocation(): void {
    this.userLocation = null;
    this.userCountry = null;
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
      (pos: GeolocationPosition) => {
        this.userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        if (this.isInternational && this.formStage === 'leaflet' && !this.isInternationalPinMoved) {
          this.moveMainPin(pos.coords.latitude, pos.coords.longitude);
        }
      },
      () => {
        this.userLocation = null;
      },
      { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 }
    );
  }

  openEdit(office: any): void {
    if (this.isLocked(office)) {
      this.showError('Esta oficina excede el límite de tu plan. Solo puedes eliminarla.');
      return;
    }
    this.editingId = office.id;
    this.isInternational = false;
    this.form = {
      name: office.name,
      code: office.code || '',
      latitude: office.latitude,
      longitude: office.longitude,
      radius_meters: office.radius_meters,
      timezone: office.timezone || 'UTC',
      country: office.country || ''
    };
    this.errors = {};
    this.selectedState = null;
    this.selectedMunicipio = null;
    this.showForm = true;
    this.formStage = 'leaflet';
    this.currentRadius = office.radius_meters;
    this.radiusDisplay = Math.round(office.radius_meters);
    setTimeout(() => this.initOfficeMap(), 50);
  }

  enterMapStage(): void {
    this.formStage = 'map';
    this.isInternational = false;
    this.selectedState = null;
    this.selectedMunicipio = null;
    this.selectedMunicipioName = '';
    setTimeout(() => this.initMap(), 50);
  }

  backToStateMap(): void {
    this.selectedMunicipio = null;
    this.selectedMunicipioName = '';
    this.formStage = 'map';
    setTimeout(() => this.initMap(), 50);
  }

  onMunicipioSelect(): void {
    this.selectedMunicipio = this.municipios.find((m: any) => m.municipio === this.selectedMunicipioName) || null;
    if (this.selectedMunicipio) {
      this.enterLeafletStage();
    }
  }

  enterLeafletStage(): void {
    this.form.country = 'Mexico';
    this.formStage = 'leaflet';
    setTimeout(() => this.initOfficeMap(), 50);
  }

  openInternationalFlow(): void {
    this.isInternational = true;
    this.isInternationalPinMoved = false;
    this.selectedMunicipio = null;
    this.selectedMunicipioName = '';
    this.formStage = 'leaflet';
    setTimeout(() => this.initOfficeMap(), 50);
    if (this.userLocation) {
      this.refreshCountry(this.userLocation.lat, this.userLocation.lng);
    }
  }

  private applyTimezoneFromCoords(lat: number, lng: number): void {
    this.form.timezone = tzlookup(lat, lng) || 'UTC';
  }

  private onOfficePositionChanged(lat: number, lng: number): void {
    this.form.latitude = +lat.toFixed(6);
    this.form.longitude = +lng.toFixed(6);
    this.applyTimezoneFromCoords(lat, lng);
    this.scheduleCountryDetection(lat, lng);
  }

  private scheduleCountryDetection(lat: number, lng: number): void {
    if (!this.isInternational) return;
    if (this.countryForCoords && this.countryForCoords.lat === lat && this.countryForCoords.lng === lng) return;
    if (this.countryDebounce) clearTimeout(this.countryDebounce);
    this.countryDebounce = setTimeout(() => {
      this.countryDebounce = null;
      this.refreshCountry(lat, lng);
    }, 600);
  }

  private async refreshCountry(lat: number, lng: number): Promise<void> {
    this.countryForCoords = { lat, lng };
    const country = await this.detectCountry(lat, lng);
    if (country) {
      this.userCountry = country;
      this.form.country = country;
    }
  }

  private async detectCountry(lat: number, lng: number): Promise<string | null> {
    try {
      const controller = new AbortController();
      const timer = setTimeout(() => controller.abort(), 4000);
      const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=es`;
      const res: any = await fetch(url, { signal: controller.signal, headers: { 'Accept-Language': 'es' } }).then((r) => r.json());
      clearTimeout(timer);
      const country: string | undefined = res?.address?.country;
      return country && country.trim() ? country : null;
    } catch {
      return null;
    }
  }

  backFromLeaflet(): void {
    if (this.editingId) {
      this.cancelForm();
      return;
    }
    if (this.isInternational) {
      this.isInternational = false;
      this.formStage = 'map';
      setTimeout(() => this.initMap(), 50);
      return;
    }
    this.formStage = 'estado';
  }

  cancelForm(): void {
    this.showForm = false;
    this.editingId = null;
    this.isInternational = false;
    this.selectedState = null;
    this.selectedMunicipio = null;
    this.selectedMunicipioName = '';
    this.searchQuery = '';
    this.searchResults = [];
    if (this.countryDebounce) clearTimeout(this.countryDebounce);
    this.countryDebounce = null;
    this.countryForCoords = null;
    this.destroyOfficeMap();
  }

  // ---------- Leaflet: mapa de la oficina ----------

  private destroyOfficeMap(): void {
    if (this.officeMap) {
      this.officeMap.remove();
      this.officeMap = null;
    }
    this.mainPin = null;
    this.radiusPin = null;
    this.radiusCircle = null;
  }

  private initOfficeMap(attempt = 0): void {
    const el = this.officeMapEl?.nativeElement as HTMLElement;
    if (!el) {
      if (attempt < 30) {
        setTimeout(() => this.initOfficeMap(attempt + 1), 80);
      }
      return;
    }

    this.destroyOfficeMap();

    const hasOffice = this.editingId !== null && this.form.latitude !== 0;
    let center: [number, number];
    if (hasOffice) {
      center = [this.form.latitude, this.form.longitude];
    } else if (this.isInternational) {
      center = this.userLocation
        ? [this.userLocation.lat, this.userLocation.lng]
        : [19.4326, -99.1332];
    } else {
      center = [this.selectedMunicipio?.ubicacion.latitud ?? 19.4326, this.selectedMunicipio?.ubicacion.longitud ?? -99.1332];
    }

    this.officeMap = L.map(el, { zoomControl: true }).setView(center as [number, number], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(this.officeMap);

    const mainIcon = L.divIcon({
      className: 'office-pin-wrap',
      html: MAIN_PIN_SVG('#ea580c'),
      iconSize: [30, 42],
      iconAnchor: [15, 42]
    });

    const radiusIcon = L.divIcon({
      className: 'office-radius-pin-wrap',
      html: RADIUS_PIN_SVG,
      iconSize: [26, 26],
      iconAnchor: [13, 13]
    });

    if (hasOffice) {
      this.currentRadius = this.form.radius_meters;
      this.currentBearing = 0;
    } else {
      this.currentRadius = RADIUS_DEFAULT;
      this.currentBearing = 0;
    }

    this.mainPin = L.marker(center as [number, number], { icon: mainIcon, draggable: true }).addTo(this.officeMap);
    this.mainPin.on('dragend', () => this.onMainPinMoved());

    this.radiusPin = L.marker(center as [number, number], { icon: radiusIcon, draggable: true, zIndexOffset: 1000 }).addTo(this.officeMap);
    this.radiusPin.on('dragend', () => this.onRadiusPinMoved());

    this.radiusCircle = L.circle(center as [number, number], {
      radius: this.currentRadius,
      color: '#ea580c',
      fillColor: '#ea580c',
      fillOpacity: 0.15,
      weight: 2
    }).addTo(this.officeMap);

    this.syncRadiusPins();
    this.form.latitude = +center[0].toFixed(6);
    this.form.longitude = +center[1].toFixed(6);
    this.applyTimezoneFromCoords(center[0], center[1]);
    this.radiusDisplay = Math.round(this.currentRadius);
  }

  private onMainPinMoved(): void {
    if (this.isInternational) this.isInternationalPinMoved = true;
    const p = this.mainPin!.getLatLng();
    this.onOfficePositionChanged(p.lat, p.lng);
    const c = [p.lat, p.lng] as [number, number];
    const h = this.radiusPin!.getLatLng();
    this.currentBearing = this.bearingDeg(c, [h.lat, h.lng]);
    this.syncRadiusPins();
  }

  private onRadiusPinMoved(): void {
    const c = this.mainPin!.getLatLng();
    const h = this.radiusPin!.getLatLng();
    const cArr = [c.lat, c.lng] as [number, number];
    const hArr = [h.lat, h.lng] as [number, number];
    this.currentBearing = this.bearingDeg(cArr, hArr);
    this.currentRadius = this.clampRadius(this.haversineMeters(cArr, hArr));
    this.syncRadiusPins();
  }

  private syncRadiusPins(): void {
    if (!this.mainPin || !this.radiusPin || !this.radiusCircle) return;
    const c = this.mainPin.getLatLng();
    this.currentRadius = this.clampRadius(this.currentRadius);
    const handlePos = this.destinationLatLng([c.lat, c.lng], this.currentRadius, this.currentBearing);
    this.radiusPin.setLatLng(handlePos);
    this.radiusCircle.setLatLng([c.lat, c.lng]).setRadius(this.currentRadius);
    this.radiusDisplay = Math.round(this.currentRadius);
    this.form.radius_meters = Math.round(this.currentRadius);
  }

  moveMainPin(lat: number, lng: number): void {
    if (!this.mainPin || !this.officeMap) return;
    this.mainPin.setLatLng([lat, lng]);
    this.officeMap.panTo([lat, lng]);
    this.onOfficePositionChanged(lat, lng);
    this.syncRadiusPins();
  }

  // ---------- Búsqueda de la oficina (Nominatim) ----------

  onSearchInput(): void {
    if (this.searchAbort) this.searchAbort.abort();
    const q = this.searchQuery.trim();
    if (!q) {
      this.searchResults = [];
      return;
    }
    this.searchAbort = new AbortController();
    const countryParam = this.isInternational ? '' : '&countrycodes=mx';
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6${countryParam}&q=${encodeURIComponent(q)}`;
    fetch(url, {
      signal: this.searchAbort.signal,
      headers: { 'Accept-Language': 'es' }
    })
      .then((r) => r.json())
      .then((res: any[]) => {
        if (!this.searchAbort || this.searchAbort.signal.aborted) return;
        this.searchResults = res;
      })
      .catch(() => {
        if (this.searchAbort && !this.searchAbort.signal.aborted) {
          this.searchResults = [];
        }
      });
  }

  onSearchSelect(r: any): void {
    const lat = +r.lat;
    const lng = +r.lon;
    this.searchResults = [];
    this.searchQuery = r.display_name;
    this.moveMainPin(lat, lng);
  }

  clearSearch(): void {
    if (this.searchAbort) this.searchAbort.abort();
    this.searchQuery = '';
    this.searchResults = [];
  }

  // ---------- Radio (haversine + clamp) ----------

  private clampRadius(r: number): number {
    return Math.max(RADIUS_MIN, Math.min(RADIUS_MAX, r));
  }

  private haversineMeters(a: [number, number], b: [number, number]): number {
    const R = 6371000;
    const dLat = this.toRad(b[0] - a[0]);
    const dLng = this.toRad(b[1] - a[1]);
    const s = Math.sin(dLat / 2) ** 2 +
      Math.cos(this.toRad(a[0])) * Math.cos(this.toRad(b[0])) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(s));
  }

  private bearingDeg(a: [number, number], b: [number, number]): number {
    const lat1 = this.toRad(a[0]);
    const lat2 = this.toRad(b[0]);
    const dLng = this.toRad(b[1] - a[1]);
    const y = Math.sin(dLng) * Math.cos(lat2);
    const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
    return (this.toDeg(Math.atan2(y, x)) + 360) % 360;
  }

  private destinationLatLng(a: [number, number], distM: number, bearingDeg: number): [number, number] {
    const R = 6371000;
    const brng = this.toRad(bearingDeg);
    const lat1 = this.toRad(a[0]);
    const lng1 = this.toRad(a[1]);
    const d = distM / R;
    const lat2 = Math.asin(Math.sin(lat1) * Math.cos(d) + Math.cos(lat1) * Math.sin(d) * Math.cos(brng));
    const lng2 = lng1 + Math.atan2(Math.sin(brng) * Math.sin(d) * Math.cos(lat1), Math.cos(d) - Math.sin(lat1) * Math.sin(lat2));
    return [this.toDeg(lat2), this.toDeg(lng2)];
  }

  private toRad(v: number): number { return v * Math.PI / 180; }
  private toDeg(v: number): number { return v * 180 / Math.PI; }

  // ---------- Persistencia ----------

  async submitForm(): Promise<void> {
    if (this.isSubmitting) return;
    if (this.form.latitude === 0 && this.form.longitude === 0) {
      this.showError('Coloca el pin sobre tu oficina.');
      return;
    }
    if (!this.form.name || !this.form.name.trim()) {
      this.errors = { ...this.errors, name: 'El nombre de la oficina es obligatorio.' };
      return;
    }

    this.isSubmitting = true;
    this.errors = {};

    if (!this.editingId && !this.canCreate) {
      this.isSubmitting = false;
      this.showError(this.overLimitMessage);
      return;
    }

    if (this.isInternational) {
      const country = await this.detectCountry(this.form.latitude, this.form.longitude);
      if (country) {
        this.userCountry = country;
        this.form.country = country;
      }
    }

    const data: any = {
      name: this.form.name.trim(),
      code: this.form.code?.trim() || null,
      latitude: this.form.latitude,
      longitude: this.form.longitude,
      radius_meters: Math.round(this.form.radius_meters),
      timezone: this.form.timezone || 'UTC',
      country: this.form.country || null
    };

    const request = this.editingId
      ? this.completeProfileService.updateOffice(this.editingId, data)
      : this.completeProfileService.createOffice(data);

    request.subscribe({
      next: () => {
        this.showForm = false;
        this.editingId = null;
        this.isSubmitting = false;
        this.isInternational = false;
        this.selectedState = null;
        this.selectedMunicipio = null;
        this.selectedMunicipioName = '';
        this.searchQuery = '';
        this.searchResults = [];
        this.destroyOfficeMap();
        this.loadOffices();
        this.loadOfficeLimit();
        this.showSuccess('Oficina guardada.');
      },
      error: (err: any) => {
        this.isSubmitting = false;
        if (err.error?.errors) {
          this.errors = err.error.errors;
        }
        this.showError(err.error?.message || 'Error al guardar.');
      }
    });
  }

  continue(): void {
    if (this.offices.length === 0) {
      this.showError('Debes crear al menos una oficina.');
      return;
    }
    this.next.emit();
  }

  deleteOffice(office: any): void {
    if (this.offices.length <= 1) {
      this.showError('Debes mantener al menos una oficina.');
      return;
    }
    if (!confirm(`¿Eliminar la oficina "${office.name}"?`)) return;

    this.completeProfileService.deleteOffice(office.id).subscribe({
      next: () => {
        this.loadOffices();
        this.loadOfficeLimit();
        this.showSuccess('Oficina eliminada.');
      },
      error: (err: any) => {
        this.showError(err.error?.message || 'Error al eliminar.');
      }
    });
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
