import { Component, inject, OnDestroy, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MyRating } from '../../core/models';
import { AuthService } from '../../core/services/auth.service';
import { RatingService } from '../../core/services/rating.service';
import { ToastService } from '../../core/services/toast.service';
import { errorMessage } from '../../core/utils/error-message';
import { ModalComponent } from '../../shared/components/modal/modal.component';
import { StarRatingComponent } from '../../shared/components/star-rating/star-rating.component';

/** Segundos en el panel antes de pedir la calificación por primera vez. */
const ASK_AFTER_SECONDS = 20;

/**
 * Pide al dueño y a los administradores calificar AsistControl. Aparece solo
 * una vez si aún no califican ("Ahora no" lo pospone 14 días) y se puede abrir
 * desde el menú del usuario para cambiar la opinión.
 */
@Component({
  selector: 'app-rating-modal',
  imports: [FormsModule, ModalComponent, StarRatingComponent],
  templateUrl: './rating-modal.component.html',
  styleUrl: './rating-modal.component.scss',
})
export class RatingModalComponent implements OnInit, OnDestroy {
  protected readonly ratings = inject(RatingService);
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);

  protected readonly existing = signal<MyRating | null>(null);
  protected readonly score = signal(0);
  protected readonly saving = signal(false);
  protected comment = '';
  protected allowPublish = true;

  private timer: ReturnType<typeof setTimeout> | undefined;

  async ngOnInit(): Promise<void> {
    const user = this.auth.user();
    if (!user?.can_rate) {
      return;
    }
    try {
      const rating = await this.ratings.mine();
      this.existing.set(rating);
      this.fill(rating);
      if (!rating && !this.ratings.isSnoozed()) {
        this.timer = setTimeout(() => this.ratings.modalOpen.set(true), ASK_AFTER_SECONDS * 1000);
      }
    } catch {
      // Si falla no debe estorbar el panel
    }
  }

  ngOnDestroy(): void {
    clearTimeout(this.timer);
  }

  protected later(): void {
    if (!this.existing()) {
      this.ratings.snooze();
    }
    this.ratings.modalOpen.set(false);
  }

  protected async save(): Promise<void> {
    if (!this.score()) {
      return;
    }
    this.saving.set(true);
    try {
      const response = await this.ratings.save({
        score: this.score(),
        comment: this.comment.trim() || null,
        allow_publish: this.allowPublish,
      });
      this.existing.set(response.rating);
      this.ratings.modalOpen.set(false);
      this.toast.success('Tu opinión nos ayuda a mejorar AsistControl.', {
        title: '¡Gracias!',
        icon: 'heart',
      });
    } catch (error) {
      this.toast.error(errorMessage(error));
    } finally {
      this.saving.set(false);
    }
  }

  private fill(rating: MyRating | null): void {
    if (rating) {
      this.score.set(rating.score);
      this.comment = rating.comment ?? '';
      this.allowPublish = rating.allow_publish;
    }
  }
}
