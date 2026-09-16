import { Routes } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { DashboardComponent } from './system/dashboard/dashboard.component';
import { View1Component } from './system/dashboard/view1/view1.component';
import { ShiftsComponent } from './system/shifts/shifts.component';
import { AppearanceSettingsComponent } from './system/settings/appearance/appearance-settings.component';
import { AttendanceComponent } from './system/attendance/attendance.component';
import { KioskComponent } from './system/attendance/kiosk/kiosk.component';
import { EmployeesComponent } from './system/employees/employees.component';
import { EmployeeDetailComponent } from './system/employees/detail/employee-detail.component';
import { OfficesComponent } from './system/offices/offices.component';
import { UsersComponent } from './system/users/users.component';
import { ProfileSettingsComponent } from './system/settings/profile/profile-settings.component';
import { GeneralSettingsComponent } from './system/settings/general/general-settings.component';
import { HolidaysSettingsComponent } from './system/settings/holidays/holidays-settings.component';
import { AuditComponent } from './system/settings/audit/audit.component';
import { AreasComponent } from './system/areas/areas.component';
import { PayrollComponent } from './system/payroll/payroll.component';
import { PeriodDetailComponent } from './system/payroll/period/period-detail.component';
import { ReportsComponent } from './system/reports/reports.component';
import { RequestsComponent } from './system/requests/requests.component';
import { NotificationsComponent } from './system/notifications/notifications.component';
import { CompletecompanyComponent } from './completecompany/completecompany.component';
import { authGuard } from './middleware/auth.guard';
import { redirectIfAuthGuard } from './guards/redirect-if-auth.guard';
import { completecompanyGuard } from './guards/completecompany.guard';

export const routes: Routes = [
    { path: 'login', component: LoginComponent, canActivate: [redirectIfAuthGuard] },
    { path: 'completecompany', component: CompletecompanyComponent, canActivate: [completecompanyGuard] },
    { path: 'kiosk', component: KioskComponent, canActivate: [authGuard] },
    { path: '', redirectTo: 'login', pathMatch: 'full' },
    {
        path: 'asistcontrol',
        component: DashboardComponent,
        canActivate: [authGuard],
        children: [
            { path: 'dashboard', component: View1Component },
            { path: 'attendance', component: AttendanceComponent },
            { path: 'employees', component: EmployeesComponent },
            { path: 'employees/:id', component: EmployeeDetailComponent },
            { path: 'offices', component: OfficesComponent },
            { path: 'areas', component: AreasComponent },
            { path: 'users', component: UsersComponent },
            { path: 'shifts', component: ShiftsComponent },
            { path: 'requests', component: RequestsComponent },
            { path: 'payroll', component: PayrollComponent },
            { path: 'payroll/periods/:id', component: PeriodDetailComponent },
            { path: 'reports', component: ReportsComponent },
            { path: 'notifications', component: NotificationsComponent },
            { path: 'settings/general', component: GeneralSettingsComponent },
            { path: 'settings/appearance', component: AppearanceSettingsComponent },
            { path: 'settings/profile', component: ProfileSettingsComponent },
            { path: 'settings/holidays', component: HolidaysSettingsComponent },
            { path: 'settings/audit', component: AuditComponent },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    }
];
