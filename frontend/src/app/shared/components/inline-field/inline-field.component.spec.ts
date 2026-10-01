import { TestBed } from '@angular/core/testing';
import { InlineFieldComponent } from './inline-field.component';

describe('InlineFieldComponent', () => {
  const settle = () => new Promise((resolve) => setTimeout(resolve));

  function setup(inputs: Record<string, unknown>) {
    TestBed.configureTestingModule({ imports: [InlineFieldComponent] });
    const fixture = TestBed.createComponent(InlineFieldComponent);
    for (const [key, value] of Object.entries(inputs)) {
      fixture.componentRef.setInput(key, value);
    }
    fixture.detectChanges();
    const el = fixture.nativeElement as HTMLElement;
    return { fixture, el };
  }

  it('shows the value and a small pencil when it can be saved', () => {
    const { el } = setup({ label: 'Puesto', value: 'Cajera', save: async () => undefined });

    expect(el.textContent).toContain('Cajera');
    expect(el.querySelector('button[aria-label="Editar Puesto"]')).not.toBeNull();
  });

  it('hides the pencil when it is read only', () => {
    const { el } = setup({
      label: 'Puesto',
      value: 'Cajera',
      editable: false,
      save: async () => undefined,
    });

    expect(el.querySelector('.pencil')).toBeNull();
  });

  it('saves the new value and closes the editor', async () => {
    const save = vi.fn().mockResolvedValue(undefined);
    const { fixture, el } = setup({ label: 'Puesto', value: 'Cajera', save });

    el.querySelector<HTMLButtonElement>('.pencil')!.click();
    fixture.detectChanges();
    await fixture.whenStable();

    const input = el.querySelector<HTMLInputElement>('input')!;
    input.value = 'Supervisora';
    input.dispatchEvent(new Event('input'));
    el.querySelector('form')!.dispatchEvent(new Event('submit'));
    await settle();
    fixture.detectChanges();

    expect(save).toHaveBeenCalledWith('Supervisora');
    expect(el.querySelector('form')).toBeNull();
  });

  it('keeps the editor open when saving fails', async () => {
    const save = vi.fn().mockRejectedValue(new Error('422'));
    const { fixture, el } = setup({ label: 'Correo', value: 'a@b.mx', type: 'email', save });

    el.querySelector<HTMLButtonElement>('.pencil')!.click();
    fixture.detectChanges();
    await fixture.whenStable();
    const input = el.querySelector<HTMLInputElement>('input')!;
    input.value = 'mal';
    input.dispatchEvent(new Event('input'));
    el.querySelector('form')!.dispatchEvent(new Event('submit'));
    await settle();
    fixture.detectChanges();

    expect(el.querySelector('form')).not.toBeNull();
  });

  it('shows the option label for selects', () => {
    const { el } = setup({
      label: 'Modalidad',
      type: 'select',
      value: 'remote',
      options: [
        { value: 'onsite', label: 'Presencial' },
        { value: 'remote', label: 'Home office' },
      ],
    });

    expect(el.textContent).toContain('Home office');
  });
});
