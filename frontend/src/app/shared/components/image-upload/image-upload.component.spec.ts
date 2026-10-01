import { TestBed } from '@angular/core/testing';
import { ImageUploadComponent } from './image-upload.component';

describe('ImageUploadComponent', () => {
  function setup(inputs: Record<string, unknown> = {}) {
    TestBed.configureTestingModule({ imports: [ImageUploadComponent] });
    const fixture = TestBed.createComponent(ImageUploadComponent);
    fixture.componentRef.setInput('photoUrl', inputs['photoUrl'] ?? null);
    fixture.componentRef.setInput('initials', inputs['initials'] ?? '');
    fixture.componentRef.setInput('label', inputs['label'] ?? 'Foto');
    fixture.componentRef.setInput('compact', inputs['compact'] ?? false);
    fixture.detectChanges();

    const el = fixture.nativeElement as HTMLElement;
    const input = el.querySelector<HTMLInputElement>('input[type="file"]')!;
    const uploads: File[] = [];
    let removed = false;
    fixture.componentInstance.upload.subscribe((file) => uploads.push(file));
    fixture.componentInstance.removed.subscribe(() => (removed = true));

    return {
      fixture,
      el,
      input,
      uploads,
      wasRemoved: () => removed,
    };
  }

  /** Dispara el cambio con un archivo inventado (`files` es de solo lectura). */
  function changeTo(ctx: ReturnType<typeof setup>, file: File): void {
    Object.defineProperty(ctx.input, 'files', { value: [file], configurable: true });
    ctx.input.dispatchEvent(new Event('change', { bubbles: true }));
    ctx.fixture.detectChanges();
  }

  it('shows the photo, the caption and a way to remove it', () => {
    const { el } = setup({ photoUrl: '/api/me/avatar?v=1', label: 'Tu foto' });

    expect(el.querySelector('img')?.getAttribute('src')).toBe('/api/me/avatar?v=1');
    expect(el.textContent).toContain('Tu foto');
    expect([...el.querySelectorAll('button')].some((b) => b.textContent?.includes('Quitar'))).toBe(
      true,
    );
  });

  it('falls back to the initials and hides "Quitar" while there is no photo', () => {
    const { el } = setup({ initials: 'MR' });

    expect(el.querySelector('img')).toBeNull();
    expect(el.textContent).toContain('MR');
    expect([...el.querySelectorAll('button')].some((b) => b.textContent?.includes('Quitar'))).toBe(
      false,
    );
  });

  it('emits the file and previews it as soon as it is picked', () => {
    const ctx = setup({ photoUrl: '/api/me/avatar?v=1' });

    changeTo(ctx, new File(['x'], 'yo.png', { type: 'image/png' }));

    expect(ctx.uploads).toHaveLength(1);
    expect(ctx.uploads[0].name).toBe('yo.png');
    expect(ctx.el.querySelector('img')?.getAttribute('src')).toMatch(/^blob:/);
  });

  it('does not emit files that are not images or are too heavy', () => {
    const ctx = setup();

    changeTo(ctx, new File(['x'], 'notas.txt', { type: 'text/plain' }));
    changeTo(ctx, new File(['x'.repeat(3 * 1024 * 1024)], 'grande.png', { type: 'image/png' }));

    expect(ctx.uploads).toHaveLength(0);
  });

  it('hides the preview in compact mode and emits when the photo is removed', () => {
    const ctx = setup({ photoUrl: '/api/me/avatar?v=1', compact: true, initials: 'MR' });

    expect(ctx.el.querySelector('img')).toBeNull();

    [...ctx.el.querySelectorAll('button')]
      .find((b) => b.textContent?.includes('Quitar'))!
      .click();

    expect(ctx.wasRemoved()).toBe(true);
  });
});
