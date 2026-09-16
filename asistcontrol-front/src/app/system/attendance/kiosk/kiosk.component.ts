import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { AttendanceService } from '../../../services/attendance.service';
import { CatalogService } from '../../../services/catalog.service';
import { Office } from '../../../models/api.models';

@Component({
  selector: 'app-kiosk',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './kiosk.component.html',
  styleUrl: './kiosk.component.css'
})
export class KioskComponent implements OnInit {

  offices: Office[] = [];
  officeId: any = '';
  mode: 'code' | 'pin' = 'code';
  code = '';
  pin = '';
  loading = false;
  error = '';
  lastResult: any = null;

  geo: { lat?: number; lng?: number } = {};

  constructor(
    private attendanceService: AttendanceService,
    private catalogService: CatalogService
  ) {}

  ngOnInit(): void {
    this.catalogService.offices().subscribe({
      next: (res) => {
        this.offices = res.offices ?? [];
        if (this.offices.length === 1) this.officeId = this.offices[0].id;
      }
    });
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => (this.geo = { lat: pos.coords.latitude, lng: pos.coords.longitude }),
        () => {}
      );
    }
  }

  get currentOffice(): Office | null {
    return this.offices.find((o) => o.id === this.officeId) ?? null;
  }

  mark(type: 'check_in' | 'check_out' | 'lunch_start' | 'lunch_end'): void {
    if (this.loading) return;
    this.error = '';
    this.lastResult = null;

    if (this.mode === 'code' && !this.code.trim()) {
      this.error = 'Ingresa el código del empleado.';
      return;
    }
    if (this.mode === 'pin' && !this.pin.trim()) {
      this.error = 'Ingresa el PIN.';
      return;
    }

    this.loading = true;
    const payload: any = {
      type,
      office_id: this.officeId || null,
      latitude: this.geo.lat,
      longitude: this.geo.lng,
    };
    if (this.mode === 'code') {
      payload.employee_code = this.code.trim().toUpperCase();
    } else {
      payload.pin = this.pin.trim();
    }

    this.attendanceService.kiosk(payload).subscribe({
      next: (res) => {
        this.loading = false;
        this.lastResult = { employee: res.employee, type: res.type };
        this.code = '';
        this.pin = '';
        setTimeout(() => (this.lastResult = null), 5000);
      },
      error: (err) => {
        this.loading = false;
        this.error = err.error?.message || 'No se pudo registrar el marcaje.';
      }
    });
  }

  actionLabel(type: string): string {
    return {
      check_in: 'Entrada',
      check_out: 'Salida',
      lunch_start: 'Inicio descanso',
      lunch_end: 'Fin descanso',
    }[type] ?? type;
  }
}
