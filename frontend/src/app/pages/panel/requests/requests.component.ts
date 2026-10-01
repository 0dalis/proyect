import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { EmployeeRequest, VacationBalance } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { ProfileService } from '../../../core/services/profile.service';
import { RequestService } from '../../../core/services/request.service';
import { errorMessage } from '../../../core/utils/error-message';
import { ModalComponent } from '../../../shared/components/modal/modal.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { REQUEST_TYPE_LABELS, STATUS_LABELS } from '../../../shared/constants/labels';
import { ToastService } from '../../../core/services/toast.service';
import { DialogService } from '../../../core/services/dialog.service';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';
import { VacationBalanceComponent } from '../../../shared/components/vacation-balance/vacation-balance.component';

@Component({
  selector: 'app-requests',
  imports: [SkeletonComponent, FormsModule, DatePipe, PageHeaderComponent, ModalComponent, VacationBalanceComponent],
  templateUrl: './requests.component.html',
  styleUrl: './requests.component.scss',
})
export class RequestsComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly dialog = inject(DialogService);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);
  private readonly requestService = inject(RequestService);
  private readonly profileService = inject(ProfileService);
  /** Saldo del propio empleado, para pedir vacaciones sabiendo cuántos días le quedan. */
  protected readonly vacation = signal<VacationBalance | null>(null);

  protected readonly labels = STATUS_LABELS;
  protected readonly types = REQUEST_TYPE_LABELS;
  protected readonly typeOptions = Object.keys(REQUEST_TYPE_LABELS);
  protected readonly tabs = [
    { value: 'pending', label: 'Pendientes' },
    { value: 'approved', label: 'Aprobadas' },
    { value: 'rejected', label: 'Rechazadas' },
    { value: '', label: 'Todas' },
  ];
  protected readonly requests = signal<EmployeeRequest[]>([]);
  protected readonly formOpen = signal(false);
  protected readonly formError = signal<string | null>(null);
  protected status = 'pending';
  protected draft = this.emptyDraft();

  ngOnInit(): Promise<void> {
    return this.load();
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    try {
      this.requests.set((await this.requestService.list({ status: this.status })).data);
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected openForm(): void {
    this.draft = this.emptyDraft();
    this.formError.set(null);
    this.formOpen.set(true);
    if (this.auth.user()?.employee) {
      this.profileService
        .summary()
        .then((summary) => this.vacation.set(summary.vacation ?? null))
        .catch(() => this.vacation.set(null));
    }
  }

  protected async review(
    request: EmployeeRequest,
    decision: 'approved' | 'rejected',
  ): Promise<void> {
    const name =
      `${request.employee?.first_name ?? ''} ${request.employee?.last_name ?? ''}`.trim();
    let notes: string | undefined;

    if (decision === 'rejected') {
      const reason = await this.dialog.prompt({
        title: 'Rechazar solicitud',
        text: `${this.types[request.type]} de ${name}. El empleado verá el motivo.`,
        inputLabel: 'Motivo del rechazo (opcional)',
        placeholder: 'Ej. Faltan documentos que lo respalden',
        confirmText: 'Rechazar',
        variant: 'danger',
      });
      if (reason === null) {
        return;
      }
      notes = reason || undefined;
    }

    try {
      await this.requestService.review(request.id, decision, notes);
      if (decision === 'approved') {
        this.toast.success(`${this.types[request.type]} de ${name} aprobada.`, {
          title: 'Solicitud aprobada',
        });
      } else {
        this.toast.info(`Se notificará a ${name}.`, {
          title: 'Solicitud rechazada',
          icon: 'x-circle-fill',
        });
      }
      await this.load();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  protected async create(): Promise<void> {
    try {
      await this.requestService.create({
        ...this.draft,
        ends_on: this.draft.ends_on || null,
        expected_time: this.draft.expected_time || null,
      });
      this.formOpen.set(false);
      this.toast.success('Tu solicitud quedó pendiente de revisión.', {
        title: 'Solicitud enviada',
        icon: 'send-check-fill',
      });
      this.status = 'pending';
      await this.load();
    } catch (error) {
      this.formError.set(errorMessage(error));
    }
  }

  private emptyDraft() {
    return { type: 'justification', starts_on: '', ends_on: '', expected_time: '', reason: '' };
  }
}
