import { Routes } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { DashboardComponent } from './system/dashboard/dashboard.component';
import { View1Component } from './system/dashboard/view1/view1.component';
import { authGuard } from './middleware/auth.guard';
import { redirectIfAuthGuard } from './guards/redirect-if-auth.guard';

export const routes: Routes = [
    { path: 'login', component: LoginComponent, canActivate: [redirectIfAuthGuard] },
    { path: '', redirectTo: 'login', pathMatch: 'full' },
    {
        path: 'asistcontrol',
        component: DashboardComponent,
        canActivate: [authGuard],
        children: [
            { path: 'dashboard', component: View1Component },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    }
];
