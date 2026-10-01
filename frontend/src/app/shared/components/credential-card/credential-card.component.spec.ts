import { TestBed } from '@angular/core/testing';
import { CredentialCompany, CredentialEmployee } from '../../../core/models';
import { CredentialCardComponent } from './credential-card.component';

describe('CredentialCardComponent', () => {
  const company: CredentialCompany = { name: 'Almacenes Demo', logo_url: '/api/company/logo?v=1' };

  const employee: CredentialEmployee = {
    public_id: 'abc',
    employee_code: 'ALDE-0006',
    first_name: 'Ana',
    last_name: 'Recursos',
    position: 'Bodeguera',
    employment_type: 'permanent',
    photo_url: '/api/employees/abc/photo?v=1',
    badge_qr: 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
    badge_issued_at: '2026-10-01T12:00:00-06:00',
    badge_expires_on: null,
    area: { name: 'Almacén' },
    office: { name: 'Planta Norte' },
  };

  function setup(face: 'front' | 'back', changes: Partial<CredentialEmployee> = {}, orientation = 'horizontal') {
    TestBed.configureTestingModule({ imports: [CredentialCardComponent] });
    const fixture = TestBed.createComponent(CredentialCardComponent);
    fixture.componentRef.setInput('employee', { ...employee, ...changes });
    fixture.componentRef.setInput('company', company);
    fixture.componentRef.setInput('orientation', orientation);
    fixture.componentRef.setInput('face', face);
    fixture.detectChanges();

    return { fixture, el: fixture.nativeElement as HTMLElement };
  }

  it('draws the front with the brand, the code, the photo and the QR', () => {
    const { el } = setup('front');

    expect(el.textContent).toContain('Almacenes Demo');
    expect(el.textContent).toContain('ALDE-0006');
    expect(el.textContent).toContain('Ana Recursos');
    expect(el.textContent).toContain('Bodeguera');
    expect(el.textContent).toContain('Almacén · Planta Norte');
    expect(el.querySelector('.photo img')?.getAttribute('src')).toBe(
      '/api/employees/abc/photo?v=1',
    );
    expect(el.querySelector('.qr')?.getAttribute('src')).toContain('data:image/svg+xml;base64,');
    expect(el.textContent).toContain('Emitida 01/10/2026');
    expect(el.textContent).not.toContain('Uso en el kiosko');
  });

  it('draws the back with the kiosk steps, the terms and the code', () => {
    const { el } = setup('back');

    expect(el.textContent).toContain('Uso en el kiosko');
    expect(el.textContent).toContain('Términos de uso');
    expect(el.textContent).toContain('PIN de 6 dígitos');
    expect(el.textContent).toContain('ALDE-0006 · Almacenes Demo');
    expect(el.querySelector('.qr')).toBeNull();
  });

  it('falls back to the initials when there is no photo', () => {
    const { el } = setup('front', { photo_url: null });

    expect(el.querySelector('.photo img')).toBeNull();
    expect(el.querySelector('.photo')?.textContent?.trim()).toBe('AR');
  });

  it('marks the temporary badge and its expiration', () => {
    const { el } = setup('front', { employment_type: 'temporary', badge_expires_on: '2026-12-31' });

    expect(el.querySelector('.temp')?.textContent).toContain('TEMPORAL');
    expect(el.querySelector('.temp')?.textContent).toContain('· 31/12/2026');
    expect(el.textContent).toContain('Vigencia 31/12/2026');
  });

  it('draws a vertical card when that orientation is picked', () => {
    const { el } = setup('front', {}, 'vertical');

    expect(el.querySelector('.cred--vertical')).not.toBeNull();
    expect(el.querySelector('.cred--horizontal')).toBeNull();
  });

  it('draws a horizontal card by default', () => {
    const { el } = setup('front');

    expect(el.querySelector('.cred--horizontal')).not.toBeNull();
    expect(el.querySelector('.cred--vertical')).toBeNull();
  });
});
