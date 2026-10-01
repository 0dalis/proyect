import { TestBed } from '@angular/core/testing';
import { ToastService } from './toast.service';

describe('ToastService', () => {
  let toast: ToastService;

  beforeEach(() => {
    vi.useFakeTimers();
    toast = TestBed.inject(ToastService);
  });

  afterEach(() => {
    toast.clear();
    vi.useRealTimers();
  });

  it('shows a toast with the defaults of its type', () => {
    toast.success('Empleado guardado');

    const [item] = toast.toasts();
    expect(item.type).toBe('success');
    expect(item.title).toBe('Listo');
    expect(item.icon).toBe('check-circle-fill');
    expect(item.position).toBe('top-end');
    expect(item.display).toBe('auto');
  });

  it('closes itself after its duration', () => {
    toast.info('Hola', { duration: 1000 });

    vi.advanceTimersByTime(999);
    expect(toast.toasts().length).toBe(1);
    vi.advanceTimersByTime(1);
    expect(toast.toasts().length).toBe(0);
  });

  it('keeps sticky toasts until dismissed', () => {
    const id = toast.error('Sin conexión', { duration: 0 });

    vi.advanceTimersByTime(60_000);
    expect(toast.toasts().length).toBe(1);
    toast.dismiss(id);
    expect(toast.toasts().length).toBe(0);
  });

  it('pauses the countdown and resumes with the time left', () => {
    const id = toast.warning('Cuidado', { duration: 1000 });

    vi.advanceTimersByTime(600);
    toast.pause(id);
    vi.advanceTimersByTime(5000);
    expect(toast.toasts().length).toBe(1);

    toast.resume(id);
    vi.advanceTimersByTime(399);
    expect(toast.toasts().length).toBe(1);
    vi.advanceTimersByTime(1);
    expect(toast.toasts().length).toBe(0);
  });

  it('stacks by position and drops the oldest past the limit', () => {
    for (let i = 1; i <= 6; i++) {
      toast.info(`Aviso ${i}`, { position: 'bottom-center' });
    }
    toast.info('Otro lado', { position: 'top-start' });

    const bottom = toast.byPosition('bottom-center').map((t) => t.message);
    expect(bottom).toEqual(['Aviso 2', 'Aviso 3', 'Aviso 4', 'Aviso 5', 'Aviso 6']);
    expect(toast.byPosition('top-start').length).toBe(1);
  });

  it('respects the display mode and custom icon', () => {
    toast.success('Checada registrada', { display: 'icon', icon: 'fingerprint' });

    expect(toast.toasts()[0].display).toBe('icon');
    expect(toast.toasts()[0].icon).toBe('fingerprint');
  });
});
