import { TestBed } from '@angular/core/testing';
import { ToastService } from '../../../core/services/toast.service';
import { ToastContainerComponent } from './toast-container.component';

describe('ToastContainerComponent', () => {
  function setup() {
    TestBed.configureTestingModule({ imports: [ToastContainerComponent] });
    const fixture = TestBed.createComponent(ToastContainerComponent);
    const toast = TestBed.inject(ToastService);
    const render = () => {
      fixture.detectChanges();
      return fixture.nativeElement as HTMLElement;
    };
    return { toast, render };
  }

  afterEach(() => TestBed.inject(ToastService).clear());

  it('renders each toast in its position with the type color and icon', () => {
    const { toast, render } = setup();
    toast.success('Guardado', { position: 'bottom-center' });
    toast.error('Falló', { position: 'top-start' });

    const el = render();
    const bottom = el.querySelector('.stack--bottom-center .toast')!;
    expect(bottom.classList).toContain('toast--success');
    expect(bottom.querySelector('.bi-check-circle-fill')).not.toBeNull();
    expect(el.querySelector('.stack--top-start .toast--error')).not.toBeNull();
  });

  it('expands an icon-only toast when tapped', () => {
    const { toast, render } = setup();
    toast.info('Checada registrada', { display: 'icon' });

    let el = render();
    const item = el.querySelector<HTMLElement>('.toast')!;
    expect(item.classList).toContain('toast--icon');
    expect(item.classList).not.toContain('toast--expanded');

    item.click();
    el = render();
    expect(el.querySelector('.toast')!.classList).toContain('toast--expanded');
    expect(toast.toasts()[0].paused).toBe(true);
  });

  it('closes with the close button', () => {
    const { toast, render } = setup();
    toast.warning('Cuidado');

    render().querySelector<HTMLButtonElement>('.toast__close')!.click();
    expect(toast.toasts().length).toBe(0);
  });

  it('puts the newest toast on top and keeps at most five', () => {
    const { toast, render } = setup();
    for (let i = 1; i <= 6; i++) {
      toast.info(`Aviso ${i}`);
    }

    const messages = Array.from(render().querySelectorAll('.stack--top-end .toast__message')).map(
      (el) => el.textContent?.trim(),
    );
    expect(messages).toEqual(['Aviso 6', 'Aviso 5', 'Aviso 4', 'Aviso 3', 'Aviso 2']);
  });
});
