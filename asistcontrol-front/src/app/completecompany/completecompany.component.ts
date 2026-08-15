import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { CompanySetupService } from '../services/public/company-setup.service';
import { PublicServicesService } from '../services/public/public-services.service';
import { Step1ProfileComponent } from './steps/step1-profile.component';
import { Step2OfficesComponent } from './steps/step2-offices.component';
import { Step3AreasComponent } from './steps/step3-areas.component';
import { Step4EmployeesComponent } from './steps/step4-employees.component';

import Toastify from 'toastify-js';

@Component({
  selector: 'app-completecompany',
  standalone: true,
  imports: [
    CommonModule,
    Step1ProfileComponent,
    Step2OfficesComponent,
    Step3AreasComponent,
    Step4EmployeesComponent,
  ],
  templateUrl: './completecompany.component.html',
  styleUrl: './completecompany.component.css'
})
export class CompletecompanyComponent implements OnInit {

  currentStep = 1;
  isLoading = true;
  isCompleting = false;
  company: any = {};
  limits: any = null;

  steps = [
    { num: 1, label: 'Perfil', icon: 'bi-building' },
    { num: 2, label: 'Oficinas', icon: 'bi-geo-alt' },
    { num: 3, label: 'Áreas', icon: 'bi-diagram-3' },
    { num: 4, label: 'Empleados', icon: 'bi-people' },
  ];

  constructor(
    private setupService: CompanySetupService,
    private publicService: PublicServicesService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadStatus();
  }

  private loadStatus(): void {
    this.isLoading = true;
    this.setupService.getStatus().subscribe({
      next: (res: any) => {
        this.company = res.company;
        this.currentStep = Math.min(res.setup_step, 4);
        this.isLoading = false;
      },
      error: () => {
        this.isLoading = false;
        this.showError('Error al cargar el estado de configuración.');
      }
    });

    this.setupService.getLimits().subscribe({
      next: (res: any) => {
        this.limits = res;
      },
      error: () => {}
    });
  }

  goToStep(step: number): void {
    if (step < 1 || step > 4 || step === this.currentStep) return;
    if (step > this.currentStep) {
      this.setupService.nextStep(this.currentStep).subscribe({
        next: (res: any) => {
          this.currentStep = res.setup_step;
        },
        error: () => this.showError('Error al avanzar de paso.')
      });
    } else {
      this.setupService.previousStep(this.currentStep).subscribe({
        next: (res: any) => {
          this.currentStep = res.setup_step;
        },
        error: () => this.showError('Error al retroceder de paso.')
      });
    }
  }

  onProfileUpdated(company: any): void {
    this.company = company;
    this.loadStatus();
  }

  completeSetup(): void {
    this.isCompleting = true;
    this.setupService.completeSetup().subscribe({
      next: (res: any) => {
        localStorage.removeItem('company_inactive');
        this.showSuccess(res.message);
        this.router.navigate(['/asistcontrol']);
      },
      error: (err: any) => {
        this.isCompleting = false;
        this.showError(err.error?.message || 'Error al finalizar la configuración.');
      }
    });
  }

  onLogout(): void {
    localStorage.removeItem('is_logged_in');
    localStorage.removeItem('company_inactive');
    this.publicService.logout().subscribe({
      next: () => this.router.navigate(['/login']),
      error: () => this.router.navigate(['/login'])
    });
  }

  private showSuccess(message: string): void {
    Toastify({
      text: message,
      duration: 3000,
      gravity: 'top',
      position: 'right',
      close: true,
      stopOnFocus: true,
      style: { background: '#16a34a' }
    }).showToast();
  }

  private showError(message: string): void {
    Toastify({
      text: message,
      duration: 4000,
      gravity: 'top',
      position: 'right',
      close: true,
      stopOnFocus: true,
      style: { background: '#dc2626' }
    }).showToast();
  }
}
