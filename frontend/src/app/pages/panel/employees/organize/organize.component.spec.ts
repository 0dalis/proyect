import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { environment } from '../../../../../environments/environment';
import { OrganizeEmployeesComponent } from './organize.component';

describe('OrganizeEmployeesComponent', () => {
  let http: HttpTestingController;
  let navigate: ReturnType<typeof vi.spyOn>;
  const api = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    });
    http = TestBed.inject(HttpTestingController);
    navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
  });

  afterEach(() => http.verify());

  const tick = () => new Promise((resolve) => setTimeout(resolve));
  const employee = (id: number, name: string) => ({
    id,
    employee_code: `ALDE-000${id}`,
    first_name: name,
    last_name: 'Prueba',
    email: null,
    office_id: null,
    shift_id: null,
    area_id: null,
    employment_type: null,
    contract_ends_on: null,
  });

  async function open() {
    const fixture = TestBed.createComponent(OrganizeEmployeesComponent);
    fixture.detectChanges();
    http.expectOne(`${api}/employees/setup`).flush({ count: 3, employees: [employee(1, 'Ana'), employee(2, 'Beto'), employee(3, 'Caro')] });
    http.expectOne(`${api}/offices`).flush([{ id: 10, name: 'Matriz' }]);
    http.expectOne((r) => r.url === `${api}/shifts`).flush([{ id: 20, office_id: 10, name: 'Matutino', starts_at: '09:00:00', ends_at: '18:00:00' }]);
    http.expectOne(`${api}/areas`).flush([{ id: 30, name: 'Ventas' }]);
    await tick();
    fixture.detectChanges();
    return fixture;
  }

  it('applies office, shift, area and type to the selected employees and saves them', async () => {
    const fixture = await open();
    const component = fixture.componentInstance as unknown as {
      toggleAll(checked: boolean): void;
      bulk: Record<string, unknown>;
      onBulkOffice(): void;
      applyToSelected(): void;
      readyCount(): number;
      save(): Promise<void>;
    };

    // Con una sola oficina ya viene propuesta
    expect(component.bulk['office_id']).toBe(10);
    component.onBulkOffice();
    component.bulk['area_id'] = 30;
    component.bulk['employment_type'] = 'permanent';
    component.toggleAll(true);
    component.applyToSelected();
    expect(component.readyCount()).toBe(3);

    const saving = component.save();
    const request = http.expectOne(`${api}/employees/organize`);
    expect(request.request.body.employees).toHaveLength(3);
    expect(request.request.body.employees[0]).toMatchObject({ office_id: 10, shift_id: 20, area_id: 30, employment_type: 'permanent', app_access: false });
    request.flush({ organized: 3, with_app: 0, pending: 0 });
    await saving;

    // Ya no queda nadie: de regreso a Empleados
    expect(navigate).toHaveBeenCalledWith(['/panel/empleados']);
  });
});
