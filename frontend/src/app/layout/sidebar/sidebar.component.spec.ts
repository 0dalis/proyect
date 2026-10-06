import { provideHttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { fakeUser } from '../../core/testing/fake-user';
import { SidebarComponent } from './sidebar.component';

describe('SidebarComponent', () => {
  function render(user: ReturnType<typeof fakeUser> | null): HTMLElement {
    TestBed.configureTestingModule({
      imports: [SidebarComponent],
      providers: [provideRouter([]), provideHttpClient()],
    });
    TestBed.inject(AuthService).user.set(user);
    const fixture = TestBed.createComponent(SidebarComponent);
    fixture.detectChanges();
    return fixture.nativeElement as HTMLElement;
  }

  const links = (el: HTMLElement) =>
    Array.from(el.querySelectorAll('nav a')).map((a) => a.textContent?.trim() ?? '');

  it('shows the company and only the links the user can open', () => {
    const el = render(fakeUser());

    expect(el.textContent).toContain('Almacenes Demo');
    expect(links(el).some((l) => l.includes('Empleados'))).toBe(true);
    expect(links(el).some((l) => l.includes('Pre-nómina'))).toBe(true);
    // Bonos: el módulo está apagado
    expect(links(el).some((l) => l.includes('Bonos'))).toBe(false);
    // Sin permiso users.manage
    expect(links(el).some((l) => l.includes('Usuarios'))).toBe(false);
  });

  it('shows the company logo and its name as the brand', () => {
    const company = fakeUser().company;
    const el = render(fakeUser({ company: { ...company, logo_url: '/api/company/logo?v=abc' } }));
    const brand = el.querySelector('a[aria-label="Ir al inicio"]')!;

    expect(brand.querySelector('img')?.getAttribute('src')).toBe('/api/company/logo?v=abc');
    expect(brand.textContent).toContain('Almacenes Demo');
    // El nombre de la empresa no se repite en la tarjeta del plan
    expect(el.textContent!.match(/Almacenes Demo/g)).toHaveLength(1);
  });

  it('falls back to the first letter of the company when it has no logo', () => {
    const el = render(fakeUser());
    const brand = el.querySelector('a[aria-label="Ir al inicio"]')!;

    expect(brand.querySelector('img')).toBeNull();
    expect(brand.querySelector('.brand-letter')?.textContent?.trim()).toBe('A');
    expect(brand.textContent).toContain('Almacenes Demo');
  });

  it('shows only personal links to a plain employee', () => {
    const el = render(fakeUser({ role: 'employee', role_label: 'Empleado', permissions: [] }));

    expect(links(el).some((l) => l.includes('Inicio'))).toBe(true);
    expect(links(el).some((l) => l.includes('Empleados'))).toBe(false);
    expect(links(el).some((l) => l.includes('Configuración'))).toBe(false);
  });

  it('keeps only one group open at a time and remembers it', () => {
    localStorage.removeItem('asist.sidebar.open');
    const el = render(fakeUser());
    const toggle = (label: string) =>
      Array.from(el.querySelectorAll<HTMLButtonElement>('button.group-toggle')).find((b) =>
        b.textContent?.includes(label),
      )!;
    const stack = (label: string) =>
      el.querySelector<HTMLElement>(`#${toggle(label).getAttribute('aria-controls')}`)!;

    // Primera vez: "Mi espacio" abierto, "Equipo" cerrado (sus enlaces no reciben foco)
    expect(toggle('Mi espacio').getAttribute('aria-expanded')).toBe('true');
    expect(stack('Equipo').hasAttribute('inert')).toBe(true);

    toggle('Equipo').click();
    TestBed.tick();
    expect(stack('Equipo').classList.contains('open')).toBe(true);
    expect(stack('Equipo').hasAttribute('inert')).toBe(false);
    // Solo un grupo abierto a la vez: "Mi espacio" se cerró
    expect(toggle('Mi espacio').getAttribute('aria-expanded')).toBe('false');
    expect(JSON.parse(localStorage.getItem('asist.sidebar.open')!)).toEqual(['Equipo']);
    localStorage.removeItem('asist.sidebar.open');
  });

  it('renders without crashing while there is no user yet', () => {
    expect(links(render(null))).toEqual([]);
  });
});
