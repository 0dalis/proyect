import { inject, Injectable } from '@angular/core';
import { CurrentUser, MySummary } from '../models';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class ProfileService {
  private readonly api = inject(ApiService);

  summary(): Promise<MySummary> {
    return this.api.get<MySummary>('me/summary');
  }

  /** Foto de la cuenta. Va en FormData: el navegador pone el boundary. */
  uploadAvatar(photo: File): Promise<{ message: string; user: CurrentUser }> {
    const form = new FormData();
    form.append('photo', photo, photo.name);
    return this.api.post('me/avatar', form);
  }

  removeAvatar(): Promise<{ message: string; user: CurrentUser }> {
    return this.api.delete('me/avatar');
  }

  updatePassword(data: {
    current_password: string;
    password: string;
    password_confirmation: string;
  }): Promise<{ message: string }> {
    return this.api.put('me/password', data);
  }

  updatePin(data: {
    current_password: string;
    pin: string;
    pin_confirmation: string;
  }): Promise<{ message: string }> {
    return this.api.put('me/pin', data);
  }
}
