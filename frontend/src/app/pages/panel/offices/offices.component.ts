import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Office } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { errorMessage } from '../../../core/utils/error-message';
import { GeofenceMapComponent } from '../../../shared/components/geofence-map/geofence-map.component';
import { ModalComponent } from '../../../shared/components/modal/modal.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { ToastService } from '../../../core/services/toast.service';
import { MEXICO_TIMEZONES } from '../../../shared/pipes/tz-date.pipe';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

interface OfficeDraft {
  id?: number;
  name: string;
  address: string;
  latitude: number | null;
  longitude: number | null;
  geofence_radius: number;
  timezone: string;
}

@Component({
  selector: 'app-offices',
  imports: [
    SkeletonComponent,
    FormsModule,
    GeofenceMapComponent,
    ModalComponent,
    PageHeaderComponent,
  ],
  templateUrl: './offices.component.html',
  styleUrl: './offices.component.scss',
})
export class OfficesComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);
  protected readonly timezones = MEXICO_TIMEZONES;
  private readonly organizationService = inject(OrganizationService);

  protected readonly offices = signal<Office[]>([]);
  protected readonly draft = signal<OfficeDraft | null>(null);
  protected readonly error = signal<string | null>(null);

  async ngOnInit(): Promise<void> {
    await this.load();
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    this.offices.set(await this.organizationService.offices());
  }

  protected open(office?: Office): void {
    this.error.set(null);
    this.draft.set({
      id: office?.id,
      name: office?.name ?? '',
      address: office?.address ?? '',
      latitude: office?.latitude ?? null,
      longitude: office?.longitude ?? null,
      geofence_radius: office?.geofence_radius ?? 50,
      timezone: office?.timezone ?? this.auth.user()?.company.timezone ?? 'America/Mexico_City',
    });
  }

  protected useMyLocation(office: OfficeDraft): void {
    navigator.geolocation?.getCurrentPosition(
      (position) =>
        this.draft.set({
          ...office,
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
        }),
      () => this.error.set('No se pudo obtener tu ubicación. Revisa los permisos del navegador.'),
    );
  }

  protected async save(office: OfficeDraft): Promise<void> {
    try {
      const payload = { ...office, geofence_radius: Number(office.geofence_radius) };
      await this.organizationService.saveOffice(payload);
      this.draft.set(null);
      this.toast.success(`${office.name} · radio de ${payload.geofence_radius} m`, {
        title: office.id ? 'Oficina actualizada' : 'Oficina creada',
        icon: 'geo-alt-fill',
      });
      await this.load();
    } catch (error) {
      this.error.set(errorMessage(error));
    }
  }

  protected isKnownZone(zone: string): boolean {
    return this.timezones.some((z) => z.value === zone);
  }
}
