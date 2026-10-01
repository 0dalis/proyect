import {
  AfterViewInit,
  Component,
  effect,
  ElementRef,
  input,
  OnDestroy,
  output,
  viewChild,
} from '@angular/core';
import * as L from 'leaflet';

const DEFAULT_CENTER: L.LatLngTuple = [19.4326, -99.1332]; // CDMX

L.Icon.Default.mergeOptions({
  iconUrl: 'leaflet/marker-icon.png',
  iconRetinaUrl: 'leaflet/marker-icon-2x.png',
  shadowUrl: 'leaflet/marker-shadow.png',
});

/**
 * Mapa con el punto de la oficina y el círculo de la geocerca.
 * Si es editable, se ubica con un clic o arrastrando el marcador.
 */
@Component({
  selector: 'app-geofence-map',
  templateUrl: './geofence-map.component.html',
  styleUrl: './geofence-map.component.scss',
})
export class GeofenceMapComponent implements AfterViewInit, OnDestroy {
  readonly latitude = input<number | null>(null);
  readonly longitude = input<number | null>(null);
  readonly radius = input(50);
  readonly editable = input(false);
  /** Punto extra, por ejemplo dónde checó un empleado */
  readonly marker = input<{ latitude: number; longitude: number; label: string } | null>(null);

  readonly locationChange = output<{ latitude: number; longitude: number }>();

  private readonly container = viewChild.required<ElementRef<HTMLDivElement>>('map');
  private map?: L.Map;
  private officeMarker?: L.Marker;
  private circle?: L.Circle;
  private extraMarker?: L.CircleMarker;

  constructor() {
    effect(() => this.render(this.latitude(), this.longitude(), this.radius(), this.marker()));
  }

  ngAfterViewInit(): void {
    this.map = L.map(this.container().nativeElement).setView(DEFAULT_CENTER, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap',
    }).addTo(this.map);

    if (this.editable()) {
      this.map.on('click', (event: L.LeafletMouseEvent) => this.emit(event.latlng));
    }

    this.render(this.latitude(), this.longitude(), this.radius(), this.marker(), true);
    // El mapa se crea dentro de un modal; recalcula su tamaño cuando ya es visible
    setTimeout(() => this.map?.invalidateSize(), 50);
  }

  ngOnDestroy(): void {
    this.map?.remove();
  }

  private emit(latlng: L.LatLng): void {
    this.locationChange.emit({
      latitude: Number(latlng.lat.toFixed(7)),
      longitude: Number(latlng.lng.toFixed(7)),
    });
  }

  private render(
    latitude: number | null,
    longitude: number | null,
    radius: number,
    marker: { latitude: number; longitude: number; label: string } | null,
    recenter = false,
  ): void {
    if (!this.map) {
      return;
    }

    if (latitude !== null && longitude !== null) {
      const point: L.LatLngTuple = [latitude, longitude];

      if (!this.officeMarker) {
        this.officeMarker = L.marker(point, { draggable: this.editable() }).addTo(this.map);
        this.officeMarker.on('dragend', () => this.emit(this.officeMarker!.getLatLng()));
        this.circle = L.circle(point, { radius, color: '#1d4ed8', fillOpacity: 0.15 }).addTo(
          this.map,
        );
        recenter = true;
      } else {
        this.officeMarker.setLatLng(point);
        this.circle!.setLatLng(point).setRadius(radius);
      }
    }

    this.extraMarker?.remove();
    if (marker) {
      this.extraMarker = L.circleMarker([marker.latitude, marker.longitude], {
        radius: 7,
        color: '#dc2626',
        fillOpacity: 0.9,
      })
        .bindTooltip(marker.label, { permanent: true, direction: 'top' })
        .addTo(this.map);
    }

    if (recenter && this.circle) {
      const bounds = this.circle.getBounds();
      this.map.fitBounds(this.extraMarker ? bounds.extend(this.extraMarker.getLatLng()) : bounds, {
        maxZoom: 18,
        padding: [30, 30],
      });
    }
  }
}
