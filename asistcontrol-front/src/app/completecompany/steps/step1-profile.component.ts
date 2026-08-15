import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CompanySetupService } from '../../services/public/company-setup.service';

import Toastify from 'toastify-js';

@Component({
  selector: 'app-step1-profile',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './step1-profile.component.html',
  styleUrl: './step1-profile.component.css'
})
export class Step1ProfileComponent implements OnInit {

  @Input() company: any = {};
  @Output() updated = new EventEmitter<any>();
  @Output() next = new EventEmitter<void>();
  @Output() skip = new EventEmitter<void>();

  name = '';
  isSubmitting = false;
  errors: any = {};

  constructor(private setupService: CompanySetupService) {}

  ngOnInit(): void {
    this.name = this.company.name || '';
  }

  onSubmit(): void {
    if (this.isSubmitting) return;
    this.isSubmitting = true;
    this.errors = {};

    this.setupService.updateProfile({ name: this.name }).subscribe({
      next: (res: any) => {
        this.updated.emit(res.company);
        this.showSuccess('Perfil de empresa actualizado.');
        this.next.emit();
      },
      error: (err: any) => {
        this.isSubmitting = false;
        if (err.error?.errors) {
          this.errors = err.error.errors;
        }
        this.showError('Error al guardar el perfil.');
      }
    });
  }

  onSkip(): void {
    this.skip.emit();
  }

  private showSuccess(msg: string): void {
    Toastify({ text: msg, duration: 2500, gravity: 'top', position: 'right', style: { background: '#16a34a' } }).showToast();
  }

  private showError(msg: string): void {
    Toastify({ text: msg, duration: 3500, gravity: 'top', position: 'right', style: { background: '#dc2626' } }).showToast();
  }
}
