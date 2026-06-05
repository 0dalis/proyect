import { Injectable, NgZone } from '@angular/core';
import { fromEvent, merge, Subject, timer } from 'rxjs';
import { switchMap, takeUntil } from 'rxjs/operators';
import { PublicServicesService } from './public-services.service';

@Injectable({
    providedIn: 'root'
})
export class InactivityService {

    private readonly TIMEOUT = 60 * 60 * 1000;
    private stop$ = new Subject<void>();
    private isRunning = false;

    constructor(
        private auth: PublicServicesService,
        private ngZone: NgZone
    ) {}

    startWatching() {

        if (this.isRunning) return;
        this.isRunning = true;

        this.stop$ = new Subject<void>();

        this.ngZone.runOutsideAngular(() => {

        const events$ = merge(
            fromEvent(document, 'mousemove'),
            fromEvent(document, 'keydown'),
            fromEvent(document, 'click'),
            fromEvent(document, 'scroll'),
            fromEvent(document, 'touchstart')
        );

        events$
            .pipe(
            takeUntil(this.stop$),
            switchMap(() => timer(this.TIMEOUT))
            )
            .subscribe(() => {
            this.ngZone.run(() => {
                this.stopWatching();
                this.auth.logout();
            });
            });

        });
    }

    stopWatching() {
        if (this.stop$) {
            this.stop$.next();
            this.stop$.complete();
        }
        this.isRunning = false;
    }
}