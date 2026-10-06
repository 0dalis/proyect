import { TestBed } from '@angular/core/testing';
import Swal from 'sweetalert2';
import { ProcessingService } from './processing.service';

describe('ProcessingService', () => {
  let processing: ProcessingService;

  beforeEach(() => {
    vi.useFakeTimers();
    processing = TestBed.inject(ProcessingService);
  });

  afterEach(() => {
    Swal.close();
    vi.useRealTimers();
  });

  const visible = () => !!Swal.getPopup()?.classList.contains('app-swal--processing');

  it('shows nothing when the action finishes quickly', () => {
    processing.begin();
    vi.advanceTimersByTime(200);
    processing.end();
    vi.advanceTimersByTime(1000);

    expect(visible()).toBe(false);
  });

  it('shows "Procesando…" for slow actions and keeps one alert for several at once', () => {
    processing.begin();
    processing.begin();
    vi.advanceTimersByTime(250);
    expect(visible()).toBe(true);
    expect(Swal.getTitle()?.textContent).toBe('Procesando…');

    processing.end();
    vi.advanceTimersByTime(1000);
    expect(visible()).toBe(true);

    processing.end();
    vi.advanceTimersByTime(500);
    expect(visible()).toBe(false);
  });

  it('wraps a task with run() and still ends when it fails', async () => {
    let fail!: (error: Error) => void;
    const result = processing.run(() => new Promise((_, reject) => (fail = reject)));

    vi.advanceTimersByTime(250);
    expect(visible()).toBe(true);

    fail(new Error('boom'));
    await expect(result).rejects.toThrow('boom');
    vi.advanceTimersByTime(500);
    expect(visible()).toBe(false);
  });
});
