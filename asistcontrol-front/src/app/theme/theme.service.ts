import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

import { AppTheme } from './theme.types';

import { DefaultTheme } from './themes/default.theme';
import { DarkTheme } from './themes/dark.theme';

type ThemeMode = 'default' | 'dark';

@Injectable({
    providedIn: 'root'
})
export class ThemeService {

    private currentTheme: AppTheme = DefaultTheme;

    private API_URL = 'http://127.0.0.1:8000/api';

    constructor(private http: HttpClient) {
        this.ensureThemeMode();
    }

    private ensureThemeMode(): void {
        const savedMode = localStorage.getItem(
            'theme-mode'
        ) as ThemeMode | null;
        if (!savedMode) {
            localStorage.setItem(
                'theme-mode',
                'default'
            );
        }
    }

    private getThemeMode(): ThemeMode {

        const mode = localStorage.getItem(
            'theme-mode'
        ) as ThemeMode | null;

        if (
            mode === 'dark' ||
            mode === 'default'
        ) {
            return mode;
        }

        return 'default';
    }

    private resolveLocalTheme(
        mode: ThemeMode
    ): AppTheme {

        switch (mode) {

            case 'dark':
                return DarkTheme;

            case 'default':
            default:
                return DefaultTheme;
        }
    }

    initTheme(userData: any): void {

        this.ensureThemeMode();

        if (userData?.theme_personalised) {

            this.loadRemoteTheme();

        } else {

            const mode = this.getThemeMode();

            const theme =
                this.resolveLocalTheme(mode);

            this.applyTheme(theme);
        }
    }

    private loadRemoteTheme(): void {

        const token = localStorage.getItem('token');

        const user = JSON.parse(
            localStorage.getItem('user') || '{}'
        );

        const headers = new HttpHeaders({
            Authorization: `Bearer ${token}`
        });

        const url =
            `${this.API_URL}/theme/json?clientId=${user.id}`;

        this.http.get<AppTheme>(
            url,
            { headers }
        ).subscribe({

            next: (theme) => {

                this.applyTheme(theme);

                if (theme.fontUrl) {
                    this.loadFont(theme.fontUrl);
                }
            },

            error: (err) => {

                console.error(
                    'Error cargando tema personalizado',
                    err
                );

                this.applyTheme(DefaultTheme);
            }
        });
    }

    applyTheme(theme: AppTheme): void {

        this.currentTheme = theme;

        const colors = theme.colors;

        Object.keys(colors).forEach((key) => {

            const cssVarName =
                `--${this.toKebabCase(key)}`;

            const value = colors[key];

            document.documentElement.style.setProperty(
                cssVarName,
                value
            );
        });
        if (theme.fontFamily) {
            document.documentElement.style.setProperty('--user-font', theme.fontFamily);
        }
        localStorage.setItem(
            'current-theme-name',
            theme.name
        );
    }

    setThemeMode(mode: ThemeMode): void {

        localStorage.setItem(
            'theme-mode',
            mode
        );

        const theme =
            this.resolveLocalTheme(mode);

        this.applyTheme(theme);
    }

    private toKebabCase(str: string): string {

        return str
            .replace(
                /([a-z0-9])([A-Z])/g,
                '$1-$2'
            )
            .toLowerCase();
    }

    loadFont(fontUrl: string): void {

        const newUserFont = new FontFace(
            'CustomUserFont',
            `url(${fontUrl})`
        );

        newUserFont.load().then((loadedFont) => {

            document.fonts.add(loadedFont);

            document.documentElement.style.setProperty(
                '--user-font',
                'CustomUserFont'
            );
        });
    }

    getCurrentTheme(): AppTheme {
        return this.currentTheme;
    }
}