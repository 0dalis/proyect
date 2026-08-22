import { Injectable, NgZone } from '@angular/core';
import { fromEvent, merge, Observable, Subject, timer } from 'rxjs';
import { switchMap, takeUntil } from 'rxjs/operators';
import { PublicServicesService } from './public-services.service';

@Injectable({
    providedIn: 'root'
})
export class InactivityService {

    private readonly TIMEOUT = 60 * 60 * 1000;
    private stop$ = new Subject<void>();
    private isRunning = false;
    private lastActivity = Date.now();

    constructor(
        private auth: PublicServicesService,
        private ngZone: NgZone
    ) {}

    startWatching() {

        if (this.isRunning) return;
        this.isRunning = true;

        this.stop$ = new Subject<void>();
        this.lastActivity = Date.now();

        this.ngZone.runOutsideAngular(() => {

            const events$ = merge(
                fromEvent(document, 'mousemove'),
                fromEvent(document, 'keydown'),
                fromEvent(document, 'click'),
                fromEvent(document, 'scroll'),
                fromEvent(document, 'touchstart')
            );

            const eventTracks$: Observable<unknown> = events$.pipe(
                switchMap(() => {
                    this.lastActivity = Date.now();
                    return timer(this.TIMEOUT);
                })
            );

            const visibility$ = fromEvent(document, 'visibilitychange').pipe(
                switchMap(() => {
                    if (document.visibilityState === 'visible'
                        && Date.now() - this.lastActivity > this.TIMEOUT) {
                        return timer(0);
                    }
                    return timer(this.TIMEOUT);
                })
            );

            merge(timer(this.TIMEOUT), eventTracks$, visibility$)
                .pipe(
                    takeUntil(this.stop$)
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
