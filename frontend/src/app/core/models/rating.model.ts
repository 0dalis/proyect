export type RatingStatus = 'pending' | 'published' | 'hidden';

/** Opinión del usuario actual sobre AsistControl (1 a 5, con medias estrellas). */
export interface MyRating {
  score: number;
  comment: string | null;
  allow_publish: boolean;
  status: RatingStatus;
  updated_at: string;
}

export interface RatingPayload {
  score: number;
  comment: string | null;
  allow_publish: boolean;
}

/** Opinión que el Super Admin eligió mostrar en la página. */
export interface Testimonial {
  id: number;
  score: number;
  comment: string;
  name: string;
  role: string;
  company: string;
}

export interface Testimonials {
  average: number;
  count: number;
  items: Testimonial[];
}
