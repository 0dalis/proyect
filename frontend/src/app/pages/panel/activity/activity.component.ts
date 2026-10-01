import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AccessUser, ActivityLogEntry } from '../../../core/models';
import { ActivityFilters, ActivityService } from '../../../core/services/activity.service';
import { ToastService } from '../../../core/services/toast.service';
import { UserAccessService } from '../../../core/services/user-access.service';
import { errorMessage } from '../../../core/utils/error-message';
import { ActivityTimelineComponent } from '../../../shared/components/activity-timeline/activity-timeline.component';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header.component';
import { SkeletonComponent } from '../../../shared/components/skeleton/skeleton.component';

/**
 * Bitácora general: cada acción del sistema, quién la hizo y cuándo. Las
 * acciones sobre un empleado (justificaciones, cambios) enlazan a su detalle.
 */
@Component({
  selector: 'app-activity',
  imports: [FormsModule, PageHeaderComponent, ActivityTimelineComponent, SkeletonComponent],
  templateUrl: './activity.component.html',
  styleUrl: './activity.component.scss',
})
export class ActivityComponent implements OnInit {
  private readonly activityService = inject(ActivityService);
  private readonly userAccess = inject(UserAccessService);
  private readonly toast = inject(ToastService);

  protected readonly entries = signal<ActivityLogEntry[]>([]);
  protected readonly actions = signal<Record<string, string>>({});
  protected readonly users = signal<AccessUser[]>([]);
  protected readonly loading = signal(true);
  protected readonly page = signal(1);
  protected readonly lastPage = signal(1);
  protected readonly total = signal(0);

  protected filters: ActivityFilters = {
    from: new Date(Date.now() - 29 * 86400000).toLocaleDateString('en-CA'),
    to: new Date().toLocaleDateString('en-CA'),
    scope: 'all',
    action: '',
    user_id: null,
    search: '',
  };

  protected readonly scopes = [
    { value: 'all', label: 'Todo', icon: 'list-ul' },
    { value: 'general', label: 'Generales', icon: 'globe2' },
    { value: 'employees', label: 'Ligadas a empleados', icon: 'person' },
  ] as const;

  async ngOnInit(): Promise<void> {
    this.userAccess
      .users()
      .then((users) => this.users.set(users))
      .catch(() => undefined);
    await this.load();
  }

  protected async load(page = 1): Promise<void> {
    this.loading.set(true);
    try {
      const result = await this.activityService.list({ ...this.filters, page });
      this.entries.set(result.data);
      this.actions.set(result.actions);
      this.page.set(result.current_page);
      this.lastPage.set(result.last_page);
      this.total.set(result.total);
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected setScope(scope: 'all' | 'general' | 'employees'): void {
    this.filters.scope = scope;
    this.load();
  }

  protected actionOptions(): { value: string; label: string }[] {
    return Object.entries(this.actions()).map(([value, label]) => ({ value, label }));
  }

  protected async exportCsv(): Promise<void> {
    try {
      await this.activityService.download(this.filters);
      this.toast.success('Incluye los filtros que tienes aplicados.', {
        title: 'Bitácora exportada',
        icon: 'file-earmark-spreadsheet',
      });
    } catch (error) {
      this.toast.error(errorMessage(error));
    }
  }
}
