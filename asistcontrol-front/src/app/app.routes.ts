import { Routes } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { IndexComponent } from './system/index/index.component';
import { authGuard } from './middleware/auth.guard';
import { redirectIfAuthGuard } from './guards/redirect-if-auth.guard';

export const routes: Routes = [
    { path : 'login', component: LoginComponent, canActivate: [redirectIfAuthGuard] },
    { path: '', redirectTo: 'login', pathMatch: 'full' },
    { path: 'welcome', component: IndexComponent, canActivate: [authGuard] }
];
