import { TestBed } from '@angular/core/testing';
import { CredentialCompany, CredentialEmployee } from '../../../core/models';
import { EmployeeService } from '../../../core/services/employee.service';
import { ToastService } from '../../../core/services/toast.service';
import { CredentialViewerComponent, readCredentialOrientation } from './credential-viewer.component';

describe('CredentialViewerComponent', () => {
  const employee: CredentialEmployee = {
    public_id: 'abc',
    employee_code: 'ALDE-0006',
    first_name: 'Ana',
    last_name: 'Recursos',
    position: 'Bodeguera',
    employment_type: 'permanent',
    photo_url: null,
    badge_qr: 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
    badge_issued_at: '2026-10-01T12:00:00-06:00',
    badge_expires_on: null,
    area: { name: 'Almacén' },
    office: { name: 'Planta Norte' },
  };

  const company: CredentialCompany = { name: 'Almacenes Demo', logo_url: null };

  const downloadBadge = vi.fn().mockResolvedValue(undefined);
  const renderBadge = vi.fn().mockResolvedValue(new Blob(['%PDF-1.4']));

  function setup() {
    TestBed.configureTestingModule({
      imports: [CredentialViewerComponent],
      providers: [
        { provide: EmployeeService, useValue: { downloadBadge, renderBadge } },
        {
          provide: ToastService,
          useValue: { success: vi.fn(), error: vi.fn() },
        },
      ],
    });

    const fixture = TestBed.createComponent(CredentialViewerComponent);
    fixture.componentRef.setInput('employee', employee);
    fixture.componentRef.setInput('company', company);
    fixture.detectChanges();

    return { fixture, el: fixture.nativeElement as HTMLElement };
  }

  function button(ctx: ReturnType<typeof setup>, label: string): HTMLButtonElement {
    return [...ctx.el.querySelectorAll('button')].find((item) =>
      item.textContent?.includes(label),
    ) as HTMLButtonElement;
  }

  beforeEach(() => {
    localStorage.clear();
    downloadBadge.mockClear();
    renderBadge.mockClear();
  });

  it('shows both sides of the credential with the employee code', () => {
    const { el } = setup();

    expect(el.querySelectorAll('.cred')).toHaveLength(2);
    expect(el.textContent).toContain('ALDE-0006');
    expect(el.textContent).toContain('Uso en el kiosko');
    expect(el.textContent).toContain('Almacenes Demo');
  });

  it('switches to vertical, draws both sides that way and remembers the choice', () => {
    const ctx = setup();

    button(ctx, 'Vertical').click();
    ctx.fixture.detectChanges();

    expect(ctx.el.querySelectorAll('.cred--vertical')).toHaveLength(2);
    expect(ctx.el.querySelectorAll('.cred--horizontal')).toHaveLength(0);
    expect(readCredentialOrientation()).toBe('vertical');
  });

  it('closes when the dialog asks to', () => {
    const ctx = setup();
    let closed = false;
    ctx.fixture.componentInstance.closed.subscribe(() => (closed = true));

    (ctx.el.querySelector('button[aria-label="Cerrar"]') as HTMLButtonElement).click();

    expect(closed).toBe(true);
  });

  it('asks the server for the PDF when the screen cannot be captured', async () => {
    const ctx = setup();
    const capture = vi
      .spyOn(ctx.fixture.componentInstance as unknown as { capture: () => Promise<null> }, 'capture')
      .mockResolvedValue(null);

    button(ctx, 'Descargar').click();
    await ctx.fixture.whenStable();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(capture).toHaveBeenCalled();
    expect(renderBadge).not.toHaveBeenCalled();
    expect(downloadBadge).toHaveBeenCalledWith(employee, 'horizontal');
  });
});
