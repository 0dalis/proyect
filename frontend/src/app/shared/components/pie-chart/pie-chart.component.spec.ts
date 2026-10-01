import { TestBed } from '@angular/core/testing';
import { PieChartComponent, PieChartSlice } from './pie-chart.component';

describe('PieChartComponent', () => {
  const slices: PieChartSlice[] = [
    { key: 'on_time', label: 'A tiempo', value: 18, color: '#10b981', icon: 'check-lg' },
    { key: 'late', label: 'Con retardo', value: 1, color: '#f59e0b', icon: 'clock' },
    { key: 'missing', label: 'Sin registro', value: 1, color: '#ef4444', icon: 'x-lg' },
    { key: 'rest', label: 'Descanso', value: 0, color: '#94a3b8', icon: 'moon' },
  ];

  function setup(inputs: Record<string, unknown>) {
    TestBed.configureTestingModule({ imports: [PieChartComponent] });
    const fixture = TestBed.createComponent(PieChartComponent);
    for (const [key, value] of Object.entries(inputs)) {
      fixture.componentRef.setInput(key, value);
    }
    fixture.detectChanges();
    return { fixture, el: fixture.nativeElement as HTMLElement };
  }

  it('lists the non-zero slices with count and percentage', () => {
    const { el } = setup({ slices });

    expect(el.querySelectorAll('.legend li').length).toBe(3);
    expect(el.querySelector('.legend')?.textContent).toContain('90%');
    expect(el.querySelector('canvas')).not.toBeNull();
  });

  it('shows every state, including zeros, in the table view', () => {
    const { fixture, el } = setup({ slices });

    (el.querySelector('button.btn') as HTMLButtonElement).click();
    fixture.detectChanges();

    expect(el.querySelectorAll('tbody tr').length).toBe(4);
    expect(el.querySelector('tbody')?.textContent).toContain('Descanso');
  });

  it('says there is no data instead of an empty pie', () => {
    const { el } = setup({ slices: slices.map((s) => ({ ...s, value: 0 })) });

    expect(el.querySelector('canvas')).toBeNull();
    expect(el.textContent).toContain('Sin datos');
  });
});
