import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CompanySetupService } from '../../services/public/company-setup.service';

import Toastify from 'toastify-js';

@Component({
  selector: 'app-step3-areas',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './step3-areas.component.html',
  styleUrl: './step3-areas.component.css'
})
export class Step3AreasComponent implements OnInit {

  @Input() limits: any = null;
  @Output() next = new EventEmitter<void>();
  @Output() back = new EventEmitter<void>();

  areas: any[] = [];
  isLoading = true;
  showForm = false;
  editingId: number | null = null;
  formName = '';
  isSubmitting = false;
  errors: any = {};

  constructor(private setupService: CompanySetupService) {}

  ngOnInit(): void {
    this.loadAreas();
  }

  loadAreas(): void {
    this.isLoading = true;
    this.setupService.getAreas().subscribe({
      next: (res: any) => {
        this.areas = res.areas;
        this.isLoading = false;
      },
      error: () => {
        this.isLoading = false;
        this.showError('Error al cargar áreas.');
      }
    });
  }

  openCreate(): void {
    this.editingId = null;
    this.formName = '';
    this.errors = {};
    this.showForm = true;
  }

  openEdit(area: any): void {
    this.editingId = area.id;
    this.formName = area.name;
    this.errors = {};
    this.showForm = true;
  }

  cancelForm(): void {
    this.showForm = false;
    this.editingId = null;
  }

  submitForm(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};

    const request = this.editingId
      ? this.setupService.updateArea(this.editingId, { name: this.formName })
      : this.setupService.createArea({ name: this.formName });

    request.subscribe({
      next: () => {
        this.showForm = false;
        this.editingId = null;
        this.isSubmitting = false;
        this.loadAreas();
        this.showSuccess('Área guardada.');
      },
      error: (err: any) => {
        this.isSubmitting = false;
        if (err.error?.errors) this.errors = err.error.errors;
        this.showError('Error al guardar.');
      }
    });
  }

  deleteArea(area: any): void {
    if (!confirm(`¿Eliminar el área "${area.name}"?`)) return;

    this.setupService.deleteArea(area.id).subscribe({
      next: () => {
        this.loadAreas();
        this.showSuccess('Área eliminada.');
      },
      error: (err: any) => {
        this.showError(err.error?.message || 'Error al eliminar.');
      }
    });
  }

  continue(): void {
    if (this.areas.length === 0) {
      this.showError('Debes crear al menos un área.');
      return;
    }
    this.next.emit();
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
