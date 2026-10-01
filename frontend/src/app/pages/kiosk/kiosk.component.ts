import { DatePipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, ElementRef, inject, OnDestroy, OnInit, signal, viewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import jsQR from 'jsqr';
import { KioskInfo, PunchResult } from '../../core/models';
import { KioskAttendance, KioskNews, KioskTerminalService } from '../../core/services/kiosk-terminal.service';
import { DialogService } from '../../core/services/dialog.service';
import { errorMessage } from '../../core/utils/error-message';
import { ThemeToggleComponent } from '../../shared/components/theme-toggle/theme-toggle.component';
import { STATUS_LABELS } from '../../shared/constants/labels';
import { TzDatePipe } from '../../shared/pipes/tz-date.pipe';

const RESULT_SECONDS = 4;
/** La consulta de asistencia se cierra sola a los 30 segundos. */
const HISTORY_SECONDS = 30;
const SAME_QR_COOLDOWN_MS = 10_000;

@Component({
  selector: 'app-kiosk',
  imports: [FormsModule, DatePipe, ThemeToggleComponent, TzDatePipe],
  templateUrl: './kiosk.component.html',
  styleUrl: './kiosk.component.scss',
})
export class KioskComponent implements OnInit, OnDestroy {
  private readonly terminal = inject(KioskTerminalService);
  private readonly dialog = inject(DialogService);

  private readonly video = viewChild<ElementRef<HTMLVideoElement>>('video');
  private readonly canvas = document.createElement('canvas');

  protected readonly labels = STATUS_LABELS;
  protected readonly info = signal<KioskInfo | null>(null);
  protected readonly now = signal(new Date());
  protected readonly news = signal<KioskNews[]>([]);
  protected readonly cameraReady = signal(false);
  protected readonly cameraError = signal<string | null>(null);
  protected readonly result = signal<PunchResult | null>(null);
  protected readonly failure = signal<string | null>(null);
  protected readonly busy = signal(false);
  protected readonly activationError = signal<string | null>(null);

  // Teclado numérico: primero número de empleado, luego PIN
  protected readonly keypadOpen = signal(false);
  /** Checar, o solo consultar su asistencia (para quien no tiene la app). */
  protected readonly keypadPurpose = signal<'punch' | 'history'>('punch');
  protected readonly history = signal<KioskAttendance | null>(null);
  protected readonly keypadStep = signal<'number' | 'pin'>('number');
  protected readonly employeeNumber = signal('');
  protected readonly pin = signal('');
  protected readonly keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'borrar', '0', 'ok'];

  protected activationCode = '';
  private token: string | null = null;
  private stream?: MediaStream;
  private timers: ReturnType<typeof setInterval>[] = [];
  private resultTimer?: ReturnType<typeof setTimeout>;
  private lastQr = { value: '', at: 0 };

  async ngOnInit(): Promise<void> {
    this.timers.push(setInterval(() => this.now.set(new Date()), 1000));
    this.token = this.terminal.storedToken();
    if (this.token) {
      await this.connect();
    }
  }

  ngOnDestroy(): void {
    this.timers.forEach(clearInterval);
    clearTimeout(this.resultTimer);
    this.stream?.getTracks().forEach((track) => track.stop());
  }

  protected async activate(): Promise<void> {
    this.token = this.activationCode.trim();
    this.activationError.set(null);
    await this.connect();
    if (this.info()) {
      this.terminal.saveToken(this.token);
    }
  }

  protected async deactivate(): Promise<void> {
    const confirmed = await this.dialog.confirm({
      title: '¿Desvincular este kiosko?',
      text: 'Necesitarás un código de activación nuevo del panel para volver a usarlo.',
      confirmText: 'Desvincular',
      variant: 'danger',
    });
    if (confirmed) {
      this.terminal.forgetToken();
      location.reload();
    }
  }

  private async connect(): Promise<void> {
    try {
      await this.terminal.prepare();
      this.info.set(await this.terminal.me(this.token!));
      await this.loadNews();
      this.timers.push(setInterval(() => this.loadNews(), 5 * 60_000));
      // Espera a que la vista del kiosko exista antes de encender la cámara
      setTimeout(() => this.startCamera());
    } catch (error) {
      this.info.set(null);
      this.activationError.set(
        error instanceof HttpErrorResponse && error.status === 401
          ? 'Código de activación no válido.'
          : errorMessage(error),
      );
    }
  }

  private async loadNews(): Promise<void> {
    try {
      this.news.set(await this.terminal.news(this.token!));
    } catch {
      // Las noticias no son críticas para checar
    }
  }

  private async startCamera(): Promise<void> {
    const video = this.video()?.nativeElement;
    if (!video || !navigator.mediaDevices?.getUserMedia) {
      this.cameraError.set('Este dispositivo no tiene cámara disponible. Usa el teclado.');
      return;
    }
    try {
      this.stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: 640, height: 480 },
      });
      video.srcObject = this.stream;
      await video.play();
      this.cameraReady.set(true);
      this.timers.push(setInterval(() => this.scan(), 300));
    } catch {
      this.cameraError.set('No se dio permiso para usar la cámara. Usa el teclado.');
    }
  }

  private scan(): void {
    const video = this.video()?.nativeElement;
    if (!video || this.busy() || this.result() || this.history() || this.keypadOpen() || video.readyState < 2) {
      return;
    }
    const frame = this.grabFrame(video, 480);
    const code = jsQR(frame.data, frame.width, frame.height, { inversionAttempts: 'dontInvert' });
    if (!code?.data) {
      return;
    }
    if (code.data === this.lastQr.value && Date.now() - this.lastQr.at < SAME_QR_COOLDOWN_MS) {
      return;
    }
    this.lastQr = { value: code.data, at: Date.now() };
    this.punch({ method: 'qr', qr: code.data });
  }

  private grabFrame(video: HTMLVideoElement, width: number): ImageData {
    const height = Math.round((video.videoHeight / video.videoWidth) * width) || 360;
    this.canvas.width = width;
    this.canvas.height = height;
    const context = this.canvas.getContext('2d', { willReadFrequently: true })!;
    context.drawImage(video, 0, 0, width, height);
    return context.getImageData(0, 0, width, height);
  }

  /** Foto de evidencia de quién checó. */
  private photo(): string | null {
    const video = this.video()?.nativeElement;
    if (!this.cameraReady() || !video) {
      return null;
    }
    this.grabFrame(video, 320);
    return this.canvas.toDataURL('image/jpeg', 0.7);
  }

  protected pressKey(key: string): void {
    const target = this.keypadStep() === 'number' ? this.employeeNumber : this.pin;
    if (key === 'borrar') {
      target.update((value) => value.slice(0, -1));
    } else if (key === 'ok') {
      this.confirmKeypad();
    } else if (target().length < (this.keypadStep() === 'number' ? 10 : 6)) {
      target.update((value) => value + key);
    }
  }

  private confirmKeypad(): void {
    if (this.keypadStep() === 'number' && this.employeeNumber()) {
      this.keypadStep.set('pin');
    } else if (this.keypadStep() === 'pin' && this.pin().length === 6) {
      const payload = { method: 'pin', employee_number: this.employeeNumber(), pin: this.pin() };
      if (this.keypadPurpose() === 'history') {
        void this.showHistory(payload);
      } else {
        void this.punch(payload);
      }
    }
  }

  protected openKeypad(purpose: 'punch' | 'history' = 'punch'): void {
    this.keypadPurpose.set(purpose);
    this.employeeNumber.set('');
    this.pin.set('');
    this.keypadStep.set('number');
    this.keypadOpen.set(true);
  }

  protected closeKeypad(): void {
    this.keypadOpen.set(false);
  }

  private async punch(payload: Record<string, string>): Promise<void> {
    this.busy.set(true);
    this.failure.set(null);
    try {
      const result = await this.terminal.punch(this.token!, { ...payload, photo: this.photo() });
      this.keypadOpen.set(false);
      this.showResult(result);
    } catch (error) {
      this.failure.set(errorMessage(error));
      this.pin.set('');
      this.resultTimer = setTimeout(() => this.failure.set(null), RESULT_SECONDS * 1000);
    } finally {
      this.busy.set(false);
    }
  }

  private async showHistory(payload: Record<string, string>): Promise<void> {
    this.busy.set(true);
    this.failure.set(null);
    try {
      this.history.set(await this.terminal.myAttendance(this.token!, payload));
      this.keypadOpen.set(false);
      clearTimeout(this.resultTimer);
      // Se cierra solo para que el siguiente no vea la asistencia de otro
      this.resultTimer = setTimeout(() => this.history.set(null), HISTORY_SECONDS * 1000);
    } catch (error) {
      this.failure.set(errorMessage(error));
      this.pin.set('');
    } finally {
      this.busy.set(false);
    }
  }

  protected closeHistory(): void {
    clearTimeout(this.resultTimer);
    this.history.set(null);
  }

  private showResult(result: PunchResult): void {
    this.result.set(result);
    clearTimeout(this.resultTimer);
    this.resultTimer = setTimeout(() => this.result.set(null), RESULT_SECONDS * 1000);
  }
}
