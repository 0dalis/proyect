import { Routes } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { IndexComponent } from './system/index/index.component';
import { authGuard } from './midleware/auth.guard';

export const routes: Routes = [
    { path : 'Login', component: LoginComponent},
    { path: '', redirectTo: 'Login', pathMatch: 'full' },
    { path: 'welcome', component: IndexComponent, canActivate: [authGuard] }
];
