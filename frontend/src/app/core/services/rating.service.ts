import { inject, Injectable, signal } from '@angular/core';
import { MyRating, RatingPayload, Testimonials } from '../models';
import { ApiService } from './api.service';

/** Días que se deja de preguntar si el usuario elige "Ahora no". */
export const RATING_SNOOZE_DAYS = 14;
const SNOOZE_KEY = 'asist.rating.snoozed_until';

/**
 * Opiniones sobre AsistControl: el dueño y los administradores califican de
 * 1 a 5 estrellas (con medias); el Super Admin elige cuáles salen en la landing.
 */
@Injectable({ providedIn: 'root' })
export class RatingService {
  private readonly api = inject(ApiService);

  /** Abre el modal de calificación desde cualquier parte del panel. */
  readonly modalOpen = signal(false);

  async mine(): Promise<MyRating | null> {
    const response = await this.api.get<{ rating: MyRating | null }>('rating');
    return response.rating;
  }

  save(payload: RatingPayload): Promise<{ message: string; rating: MyRating }> {
    return this.api.put('rating', payload);
  }

  testimonials(): Promise<Testimonials> {
    return this.api.get<Testimonials>('testimonials');
  }

  snooze(days = RATING_SNOOZE_DAYS): void {
    try {
      localStorage.setItem(SNOOZE_KEY, String(Date.now() + days * 86_400_000));
    } catch {
      // Sin almacenamiento local: se volverá a preguntar en la próxima visita
    }
  }

  isSnoozed(): boolean {
    try {
      return Number(localStorage.getItem(SNOOZE_KEY) ?? 0) > Date.now();
    } catch {
      return false;
    }
  }
}
