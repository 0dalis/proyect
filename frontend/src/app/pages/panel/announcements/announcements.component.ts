import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Announcement, Area, Employee, Office } from '../../../core/models';
import { AnnouncementService } from '../../../core/services/announcement.service';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { errorMessage } from '../../../core/utils/error-message';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { ToastService } from '../../../core/services/toast.service';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

type Audience = 'all' | 'areas' | 'offices' | 'roles' | 'employees';

@Component({
  selector: 'app-announcements',
  imports: [SkeletonComponent, FormsModule, DatePipe, PageHeaderComponent],
  templateUrl: './announcements.component.html',
  styleUrl: './announcements.component.scss',
})
export class AnnouncementsComponent implements OnInit {
  /** Primera carga en curso: se muestra el skeleton. */
  protected readonly loading = signal(true);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);
  private readonly announcementService = inject(AnnouncementService);
  private readonly organizationService = inject(OrganizationService);
  private readonly employeeService = inject(EmployeeService);

  protected readonly announcements = signal<Announcement[]>([]);
  protected readonly areas = signal<Area[]>([]);
  protected readonly offices = signal<Office[]>([]);
  protected readonly employees = signal<Employee[]>([]);
  protected draft = this.emptyDraft();

  async ngOnInit(): Promise<void> {
    await this.load();
    if (this.auth.can('organization.manage')) {
      const [areas, offices] = await Promise.all([
        this.organizationService.areas(),
        this.organizationService.offices(),
      ]);
      this.areas.set(areas);
      this.offices.set(offices);
    }
    if (this.auth.can('employees.view')) {
      this.employees.set(await this.employeeService.active());
    }
  }

  protected async load(): Promise<void> {
    try {
      await this.fetch();
    } finally {
      this.loading.set(false);
    }
  }

  private async fetch(): Promise<void> {
    this.announcements.set((await this.announcementService.sent()).data);
  }

  protected async send(): Promise<void> {
    try {
      const result = await this.announcementService.send({
        ...this.draft,
        link_url: this.draft.link_url || null,
        publish_at: this.draft.publish_at || null,
        audience_ids: this.draft.audience_type === 'all' ? null : this.draft.audience_ids,
      });
      this.toast.success(`Llegará a ${result.recipients_count} empleado(s).`, {
        title: 'Aviso enviado',
        icon: 'megaphone-fill',
      });
      this.draft = this.emptyDraft();
      await this.load();
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }

  private emptyDraft() {
    return {
      title: '',
      body: '',
      link_url: '',
      audience_type: 'all' as Audience,
      audience_ids: [] as (number | string)[],
      show_on_kiosk: false,
      publish_at: '',
    };
  }
}
