import { Component, inject, input, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/services/api.service';
import { ToastService } from '../../core/services/toast.service';
import { errorMessage } from '../../core/utils/error-message';
import { ThemeToggleComponent } from '../../shared/components/theme-toggle/theme-toggle.component';

/**
 * Valoración final. El enlace es único y llega en el último correo, cuando
 * ya se eliminaron los datos de la empresa. Solo se puede responder una vez.
 */
@Component({
  selector: 'app-feedback',
  imports: [FormsModule, RouterLink, ThemeToggleComponent],
  templateUrl: './feedback.component.html',
  styleUrl: './feedback.component.scss',
  host: { class: 'grid min-h-screen place-items-center px-4 py-10' },
})
export class FeedbackComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly token = input.required<string>();

  protected readonly state = signal<'loading' | 'form' | 'done' | 'invalid'>('loading');
  protected readonly companyName = signal('');
  protected readonly rating = signal(0);
  protected readonly hovered = signal(0);
  protected readonly sending = signal(false);
  protected comment = '';

  protected readonly stars = [1, 2, 3, 4, 5];
  protected readonly labels = ['', 'Muy mala', 'Mala', 'Regular', 'Buena', 'Excelente'];

  async ngOnInit(): Promise<void> {
    try {
      const info = await this.api.get<{ company_name: string; submitted: boolean }>(
        `feedback/${this.token()}`,
      );
      this.companyName.set(info.company_name);
      this.state.set(info.submitted ? 'done' : 'form');
    } catch {
      this.state.set('invalid');
    }
  }

  protected async send(): Promise<void> {
    this.sending.set(true);
    try {
      await this.api.post(`feedback/${this.token()}`, {
        rating: this.rating(),
        comment: this.comment.trim() || null,
      });
      this.state.set('done');
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.sending.set(false);
    }
  }
}
