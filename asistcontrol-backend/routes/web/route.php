<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\web\AttendanceController;
use App\Http\Controllers\web\AuditController;
use App\Http\Controllers\web\CompanyCompleteController;
use App\Http\Controllers\web\CredentialController;
use App\Http\Controllers\web\DashboardController;
use App\Http\Controllers\web\EmployeeController;
use App\Http\Controllers\web\ExportController;
use App\Http\Controllers\web\HolidayController;
use App\Http\Controllers\web\LoanController;
use App\Http\Controllers\web\NotificationController;
use App\Http\Controllers\web\PayrollController;
use App\Http\Controllers\web\ProfileController;
use App\Http\Controllers\web\ReportController;
use App\Http\Controllers\web\RequestController;
use App\Http\Controllers\web\ShiftsController;
use App\Http\Controllers\web\UserController;

Route::get('/user-permissions', [PublicController::class, 'getUserPermissions']);
Route::post('/logout', [PublicController::class, 'logout']);

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::prefix('shifts')->group(function () {
    Route::get('/', [ShiftsController::class, 'index']);
    Route::post('/', [ShiftsController::class, 'storeShift']);
    Route::post('/assign', [ShiftsController::class, 'assignShift']);
    Route::put('/{id}', [ShiftsController::class, 'updateShift']);
    Route::delete('/{id}', [ShiftsController::class, 'deleteShift']);
});

Route::prefix('billing')->group(function () {
    Route::post('/checkout', [StripeController::class, 'checkout']);
    Route::get('/status', [StripeController::class, 'status']);
    Route::get('/portal', [StripeController::class, 'billingPortal']);
    Route::post('/cancel', [StripeController::class, 'cancel']);
});

Route::prefix('company-setup')->group(function () {
    Route::get('/status', [CompanyCompleteController::class, 'status']);
    Route::get('/limits', [CompanyCompleteController::class, 'limits']);
    Route::get('/office-limit', [CompanyCompleteController::class, 'officeLimit'])->middleware('role:owner');
    Route::put('/profile', [CompanyCompleteController::class, 'updateProfile']);
    Route::get('/offices', [CompanyCompleteController::class, 'offices']);
    Route::post('/offices', [CompanyCompleteController::class, 'storeOffice']);
    Route::put('/offices/{id}', [CompanyCompleteController::class, 'updateOffice']);
    Route::delete('/offices/{id}', [CompanyCompleteController::class, 'deleteOffice']);
    Route::get('/areas', [CompanyCompleteController::class, 'areas']);
    Route::post('/areas', [CompanyCompleteController::class, 'storeArea']);
    Route::put('/areas/{id}', [CompanyCompleteController::class, 'updateArea']);
    Route::delete('/areas/{id}', [CompanyCompleteController::class, 'deleteArea']);
    Route::get('/employees', [CompanyCompleteController::class, 'employees']);
    Route::post('/employees', [CompanyCompleteController::class, 'storeEmployee']);
    Route::post('/employees/generate-code', [CompanyCompleteController::class, 'generateEmployeeCode']);
    Route::put('/employees/{id}', [CompanyCompleteController::class, 'updateEmployee']);
    Route::delete('/employees/{id}', [CompanyCompleteController::class, 'deleteEmployee']);
    Route::post('/next-step', [CompanyCompleteController::class, 'nextStep']);
    Route::post('/previous-step', [CompanyCompleteController::class, 'previousStep']);
    Route::post('/complete', [CompanyCompleteController::class, 'complete']);
});

Route::prefix('v1')->group(function () {

});

// ---------------- Asistencia ----------------
Route::prefix('attendance')->group(function () {
    Route::get('/', [AttendanceController::class, 'index']);
    Route::get('/summary', [AttendanceController::class, 'summary']);
    Route::post('/correct', [AttendanceController::class, 'correct']);
    Route::post('/kiosk', [AttendanceController::class, 'kiosk']);
    Route::get('/{id}', [AttendanceController::class, 'show']);
});

// ---------------- Empleados (catálogo) ----------------
Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::post('/generate-code', [EmployeeController::class, 'generateCode']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::get('/{id}', [EmployeeController::class, 'show']);
    Route::put('/{id}', [EmployeeController::class, 'update']);
    Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    Route::put('/{id}/compensation', [EmployeeController::class, 'updateCompensation']);
    Route::put('/{id}/concepts', [EmployeeController::class, 'syncConcepts']);

    // Credencial / QR
    Route::get('/{id}/credential', [CredentialController::class, 'show']);
    Route::put('/{id}/credential', [CredentialController::class, 'update']);
    Route::patch('/{id}/credential', [CredentialController::class, 'update']);
    Route::post('/{id}/credential/qr', [CredentialController::class, 'regenerateQr']);
    Route::post('/{id}/credential/pdf', [CredentialController::class, 'uploadPdf']);
    Route::get('/{id}/credential/pdf', [CredentialController::class, 'downloadPdf']);
    Route::post('/{id}/photo', [CredentialController::class, 'uploadPhoto']);
    Route::delete('/{id}/photo', [CredentialController::class, 'removePhoto']);
});

// ---------------- Solicitudes ----------------
Route::prefix('requests')->group(function () {
    Route::get('/', [RequestController::class, 'index']);
    Route::post('/', [RequestController::class, 'store']);
    Route::put('/{id}', [RequestController::class, 'update']);
    Route::post('/{id}/approve', [RequestController::class, 'approve']);
    Route::post('/{id}/reject', [RequestController::class, 'reject']);
});
Route::get('/vacations/balances', [RequestController::class, 'balances']);
Route::put('/vacations/balances/{employeeId}', [RequestController::class, 'updateBalance']);
Route::get('/vacations/calendar', [RequestController::class, 'calendar']);

// ---------------- Notificaciones ----------------
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/recipients', [NotificationController::class, 'recipients']);
    Route::post('/preview', [NotificationController::class, 'preview']);
    Route::post('/', [NotificationController::class, 'store']);
    Route::put('/{id}', [NotificationController::class, 'update']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    Route::post('/{id}/send', [NotificationController::class, 'send']);
    Route::post('/{id}/read', [NotificationController::class, 'read']);
});

// ---------------- Nómina ----------------
Route::prefix('payroll')->group(function () {
    Route::get('/settings', [PayrollController::class, 'settings']);
    Route::put('/settings', [PayrollController::class, 'updateSettings']);

    Route::get('/concepts', [PayrollController::class, 'concepts']);
    Route::post('/concepts', [PayrollController::class, 'storeConcept']);
    Route::put('/concepts/{id}', [PayrollController::class, 'updateConcept']);
    Route::delete('/concepts/{id}', [PayrollController::class, 'deleteConcept']);

    Route::get('/periods', [PayrollController::class, 'periods']);
    Route::post('/periods', [PayrollController::class, 'storePeriod']);
    Route::get('/periods/{id}', [PayrollController::class, 'showPeriod']);
    Route::post('/periods/{id}/calculate', [PayrollController::class, 'calculatePeriod']);
    Route::post('/periods/{id}/close', [PayrollController::class, 'closePeriod']);
    Route::delete('/periods/{id}', [PayrollController::class, 'deletePeriod']);
});

// ---------------- Reportes ----------------
Route::prefix('reports')->group(function () {
    Route::get('/attendance', [ReportController::class, 'attendance']);
    Route::get('/attendance/employee/{id}', [ReportController::class, 'employee']);
    Route::get('/payroll', [ReportController::class, 'payroll']);
});

// ---------------- Exportaciones ----------------
Route::get('/exports/payslip/{itemId}', [ExportController::class, 'payslip']);
Route::get('/exports/{resource}', [ExportController::class, 'export']);

// ---------------- Préstamos ----------------
Route::get('/employees/{employeeId}/loans', [LoanController::class, 'index']);
Route::post('/employees/{employeeId}/loans', [LoanController::class, 'store']);
Route::put('/loans/{id}', [LoanController::class, 'update']);
Route::delete('/loans/{id}', [LoanController::class, 'destroy']);

// ---------------- Días festivos ----------------
Route::get('/holidays', [HolidayController::class, 'index']);
Route::post('/holidays', [HolidayController::class, 'store']);
Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);

// ---------------- Auditoría ----------------
Route::get('/audit', [AuditController::class, 'index']);

// ---------------- Usuarios de la empresa ----------------
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/roles', [UserController::class, 'roles']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::post('/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

// ---------------- Perfil y seguridad ----------------
Route::prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'show']);
    Route::put('/', [ProfileController::class, 'update']);
    Route::put('/password', [ProfileController::class, 'updatePassword']);
});
