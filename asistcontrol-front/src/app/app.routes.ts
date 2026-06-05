import { Routes } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { IndexComponent } from './system/index/index.component';
import { authGuard } from './middleware/auth.guard';

export const routes: Routes = [
    { path : 'login', component: LoginComponent},
    { path: '', redirectTo: 'login', pathMatch: 'full' },
    { path: 'welcome', component: IndexComponent, canActivate: [authGuard] }
];
