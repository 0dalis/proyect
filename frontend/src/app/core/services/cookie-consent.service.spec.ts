import { TestBed } from '@angular/core/testing';
import { CookieConsentService } from './cookie-consent.service';

describe('CookieConsentService', () => {
  afterEach(() => localStorage.clear());

  it('asks until the user answers and then remembers', () => {
    const consent = TestBed.inject(CookieConsentService);
    expect(consent.choice()).toBeNull();

    consent.accept('essential');
    expect(consent.choice()).toBe('essential');
    expect(JSON.parse(localStorage.getItem('asist.cookie-consent')!).choice).toBe('essential');
  });

  it('asks again when the policy version changes', () => {
    localStorage.setItem(
      'asist.cookie-consent',
      JSON.stringify({ choice: 'all', version: '2020-01', at: '2020-01-01' }),
    );

    expect(TestBed.inject(CookieConsentService).choice()).toBeNull();
  });
});
