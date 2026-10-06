import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { ProcessingService } from '../services/processing.service';
import { processingInterceptor, silent } from './processing.interceptor';

describe('processingInterceptor', () => {
  let http: HttpClient;
  let controller: HttpTestingController;
  let begin: ReturnType<typeof vi.spyOn>;
  let end: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([processingInterceptor])),
        provideHttpClientTesting(),
      ],
    });
    http = TestBed.inject(HttpClient);
    controller = TestBed.inject(HttpTestingController);
    const processing = TestBed.inject(ProcessingService);
    begin = vi.spyOn(processing, 'begin').mockImplementation(() => undefined);
    end = vi.spyOn(processing, 'end').mockImplementation(() => undefined);
  });

  afterEach(() => controller.verify());

  it('tracks actions that save, delete or download until they finish', () => {
    http.post('/api/employees', {}).subscribe({ error: () => undefined });
    http.get('/api/badges.pdf', { responseType: 'blob' }).subscribe();
    expect(begin).toHaveBeenCalledTimes(2);
    expect(end).not.toHaveBeenCalled();

    controller.expectOne('/api/employees').flush({}, { status: 422, statusText: 'Unprocessable' });
    controller.expectOne('/api/badges.pdf').flush(new Blob());
    expect(end).toHaveBeenCalledTimes(2);
  });

  it('skips queries, background requests and the kiosk', () => {
    http.get('/api/employees').subscribe();
    http.post('/api/notifications/1/read', {}, { context: silent() }).subscribe();
    http.post('/api/kiosk/punch', {}, { headers: { 'X-Kiosk-Token': 't' } }).subscribe();

    controller.match(() => true).forEach((request) => request.flush({}));
    expect(begin).not.toHaveBeenCalled();
  });
});
