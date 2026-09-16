import { Component, ElementRef, EventEmitter, Input, OnInit, Output, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import * as QRCode from 'qrcode';
import { jsPDF } from 'jspdf';
import html2canvas from 'html2canvas';

import Toastify from 'toastify-js';

import { EmployeesService } from '../../../services/employees.service';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-credential',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './credential.component.html',
  styleUrl: './credential.component.css'
})
export class CredentialComponent implements OnInit {

  @Input() employeeId!: number;
  @Output() close = new EventEmitter<void>();

  @ViewChild('frontEl') frontEl!: ElementRef<HTMLElement>;
  @ViewChild('backEl') backEl!: ElementRef<HTMLElement>;

  data: any = null;
  qrDataUrl = '';
  loading = true;
  exporting = false;

  orientation: 'horizontal' | 'vertical' = 'horizontal';
  downloadEnabled = false;
  design = { accent: '#4f46e5', showPhoto: true, showArea: true, showOffice: true, showPosition: true };

  private mediaBase = environment.apiUrl.replace(/\/api\/?$/, '');

  constructor(private employeesService: EmployeesService) {}

  ngOnInit(): void {
    this.load();
  }

  get photoUrl(): string | null {
    const path = this.data?.credential?.photo_path;
    return path ? `${this.mediaBase}/storage/${path}` : null;
  }

  get logoUrl(): string | null {
    const path = this.data?.company?.logo_path;
    return path ? `${this.mediaBase}/storage/${path}` : null;
  }

  get initials(): string {
    const first = this.data?.employee?.first_name?.charAt(0) ?? '';
    const last = this.data?.employee?.last_name?.charAt(0) ?? '';
    return (first + last).toUpperCase() || '?';
  }

  load(): void {
    this.loading = true;
    this.employeesService.credential(this.employeeId).subscribe({
      next: async (res) => {
        this.data = res;
        this.orientation = res.credential?.orientation ?? 'horizontal';
        this.downloadEnabled = !!res.credential?.download_enabled;
        if (res.credential?.design) {
          this.design = { ...this.design, ...res.credential.design };
        }
        try {
          this.qrDataUrl = await QRCode.toDataURL(res.credential.qr_payload ?? '', { width: 360, margin: 1 });
        } catch {
          this.qrDataUrl = '';
        }
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.showError('No se pudo cargar la credencial.');
      }
    });
  }

  setOrientation(orientation: 'horizontal' | 'vertical'): void {
    this.orientation = orientation;
    this.persist();
  }

  persist(): void {
    if (!this.data) return;
    this.employeesService.updateCredential(this.employeeId, {
      orientation: this.orientation,
      design: this.design,
      download_enabled: this.downloadEnabled,
    }).subscribe({ next: () => {}, error: () => {} });
  }

  toggleDownload(): void {
    this.persist();
    this.showSuccess(this.downloadEnabled ? 'Descarga móvil habilitada.' : 'Descarga móvil deshabilitada.');
  }

  regenerateQr(): void {
    this.employeesService.regenerateQr(this.employeeId).subscribe({
      next: async (res) => {
        this.data.credential = res.credential;
        this.qrDataUrl = await QRCode.toDataURL(res.credential.qr_payload ?? '', { width: 360, margin: 1 });
        this.showSuccess('Código QR regenerado.');
      },
      error: () => this.showError('No se pudo regenerar el QR.')
    });
  }

  onPhotoSelected(event: any): void {
    const file = event.target?.files?.[0];
    if (!file) return;
    this.employeesService.uploadPhoto(this.employeeId, file).subscribe({
      next: (res) => {
        this.data.credential = res.credential;
        this.showSuccess('Foto actualizada.');
      },
      error: (err) => this.showError(err.error?.message || 'No se pudo subir la foto.')
    });
  }

  removePhoto(): void {
    this.employeesService.removePhoto(this.employeeId).subscribe({
      next: () => {
        if (this.data?.credential) this.data.credential.photo_path = null;
        this.showSuccess('Foto eliminada.');
      },
      error: () => this.showError('No se pudo eliminar la foto.')
    });
  }

  async exportPdf(): Promise<void> {
    if (this.exporting || !this.data) return;
    this.exporting = true;

    try {
      const horizontal = this.orientation === 'horizontal';
      const [w, h] = horizontal ? [85.6, 54] : [54, 85.6];

      const frontCanvas = await html2canvas(this.frontEl.nativeElement, { scale: 3, backgroundColor: '#ffffff', useCORS: true });
      const backCanvas = await html2canvas(this.backEl.nativeElement, { scale: 3, backgroundColor: '#ffffff', useCORS: true });

      const pdf = new jsPDF({ orientation: horizontal ? 'landscape' : 'portrait', unit: 'mm', format: [w, h] });
      pdf.addImage(frontCanvas.toDataURL('image/png'), 'PNG', 0, 0, w, h);
      pdf.addPage([w, h], horizontal ? 'landscape' : 'portrait');
      pdf.addImage(backCanvas.toDataURL('image/png'), 'PNG', 0, 0, w, h);

      const blob = pdf.output('blob');
      const filename = `credencial_${this.data.employee.employee_code}.pdf`;

      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      this.employeesService.uploadCredentialPdf(this.employeeId, blob).subscribe({
        next: () => {
          this.exporting = false;
          this.showSuccess('PDF exportado y guardado para reimpresión.');
        },
        error: () => {
          this.exporting = false;
          this.showSuccess('PDF exportado.');
        }
      });
    } catch {
      this.exporting = false;
      this.showError('No se pudo generar el PDF.');
    }
  }

  print(): void {
    window.print();
  }

  closeModal(): void {
    this.close.emit();
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
