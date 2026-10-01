<?php

namespace Database\Seeders;

use App\Actions\ActivateCompany;
use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Enums\CompanyStatus;
use App\Models\Area;
use App\Models\BonusRule;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Plan;
use App\Models\Shift;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Historial de checadas y empresas de ejemplo para que el tablero del
 * Super Admin y los reportes tengan datos. Solo para desarrollo.
 */
class DemoHistorySeeder extends Seeder
{
    private const COMPANIES = [
        ['Logística del Bajío', 'basico', 'active', 11, 18],
        ['Farmacias Luna', 'basico', 'active', 9, 22],
        ['Café Aroma', 'free', 'active', 8, 5],
        ['Constructora Norte', 'plus', 'active', 7, 40],
        ['Clínica Santa Fe', 'plus', 'past_due', 5, 28],
        ['Grupo Industrial Sierra', 'premium', 'active', 4, 60],
        ['Hotel Mar Azul', 'premium', 'trial', 0, 35],
        ['Panadería La Espiga', 'basico', 'trial', 0, 8],
        ['Taller Mecánico Ruiz', 'free', 'suspended', 3, 4],
        ['Despacho Contable MG', 'basico', 'pending', 0, 0],
    ];

    public function run(ActivateCompany $activateCompany, TenantManager $tenants, RegisterAttendance $registerAttendance): void
    {
        mt_srand(2026);

        $demo = Company::query()->where('slug', 'almacenes-demo')->firstOrFail();
        $demo->update(['payroll_enabled' => true, 'bonuses_enabled' => true, 'created_at' => now()->subMonths(10)]);
        $tenants->connect($demo);
        $this->prepareDemoPayroll();
        $this->punchHistory($demo, $tenants, $registerAttendance, 30);

        foreach (self::COMPANIES as $i => [$name, $planSlug, $status, $monthsAgo, $employees]) {
            $company = $this->createCompany($name, $planSlug, $i, $activateCompany);
            $registeredAt = now()->subMonths($monthsAgo)->subDays(mt_rand(0, 20));

            if ($status !== 'pending') {
                $tenants->connect($company);
                $this->createEmployees($employees);
                $this->punchHistory($company, $tenants, $registerAttendance, 14);
            }

            $company->forceFill([
                'status' => CompanyStatus::from($status),
                'created_at' => $registeredAt,
                'trial_ends_at' => $status === 'trial' ? now()->addDays(mt_rand(2, 12)) : $company->trial_ends_at,
                'extra_employee_blocks' => $planSlug === 'free' ? 0 : mt_rand(0, 2),
                'extra_offices' => in_array($planSlug, ['plus', 'premium'], true) ? mt_rand(0, 2) : 0,
            ])->save();
        }

        $tenants->forget();
    }

    private function createCompany(string $name, string $planSlug, int $index, ActivateCompany $activateCompany): Company
    {
        $company = Company::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'plan_id' => Plan::query()->where('slug', $planSlug)->value('id'),
            'status' => CompanyStatus::Pending,
        ]);

        $owner = User::query()->create([
            'company_id' => $company->id,
            'name' => "Dueño {$name}",
            'email' => "owner{$index}@empresas.test",
            'password' => 'password',
            'is_owner' => true,
        ]);

        if ($index !== 9) {
            $owner->markEmailAsVerified();
            $activateCompany->handle($company->refresh());
        }

        return $company->refresh();
    }

    private function createEmployees(int $count): void
    {
        $office = Office::query()->where('is_default', true)->first();
        $office->update(['latitude' => 19.4 + mt_rand(0, 900) / 10000, 'longitude' => -99.2 + mt_rand(0, 900) / 10000]);
        $shift = Shift::query()->where('is_default', true)->first();
        $area = Area::query()->first();
        $firstNames = ['María', 'José', 'Guadalupe', 'Juan', 'Fernanda', 'Luis', 'Sofía', 'Miguel', 'Valeria', 'Diego', 'Camila', 'Andrés'];
        $lastNames = ['García', 'Hernández', 'López', 'Martínez', 'González', 'Pérez', 'Rodríguez', 'Sánchez', 'Ramírez', 'Torres'];

        for ($i = 1; $i <= $count; $i++) {
            $employee = new Employee([
                'first_name' => $firstNames[array_rand($firstNames)],
                'last_name' => $lastNames[array_rand($lastNames)],
                'office_id' => $office->id,
                'shift_id' => $shift->id,
                'area_id' => $area->id,
                'employment_type' => 'permanent',
                'work_mode' => 'onsite',
                'hired_on' => now()->subYear(),
            ]);
            $employee->employee_number = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $employee->setPin('123456');
            $employee->save();
        }
    }

    private function prepareDemoPayroll(): void
    {
        Employee::query()->each(fn (Employee $employee) => $employee->update([
            'salary' => [6000, 7500, 9000, 12000][mt_rand(0, 3)],
            'salary_period' => 'biweekly',
            'hired_on' => now()->subYear(),
        ]));

        BonusRule::query()->firstOrCreate(['name' => 'Bono de puntualidad'], [
            'period' => 'biweekly', 'amount_type' => 'fixed', 'amount' => 500,
            'conditions' => [['metric' => 'unjustified_lates', 'operator' => '<', 'value' => 3]],
        ]);
        BonusRule::query()->firstOrCreate(['name' => 'Bono de asistencia'], [
            'period' => 'biweekly', 'amount_type' => 'percent', 'amount' => 10,
            'conditions' => [['metric' => 'unjustified_absences', 'operator' => '=', 'value' => 0]],
        ]);
    }

    /**
     * Entrada y salida de cada día laborable: la mayoría a tiempo, algunos
     * retardos y algunas faltas.
     */
    private function punchHistory(Company $company, TenantManager $tenants, RegisterAttendance $registerAttendance, int $days): void
    {
        $tenants->connect($company);
        $employees = Employee::query()->with('shift', 'office')->get();

        for ($offset = $days; $offset >= 1; $offset--) {
            foreach ($employees as $employee) {
                $day = now($employee->office->timezone)->startOfDay()->subDays($offset);

                if (! $employee->shift->isActiveOn($day->dayOfWeekIso) || mt_rand(1, 100) <= 5) {
                    continue;
                }

                $roll = mt_rand(1, 100);
                $minutes = match (true) {
                    $roll <= 78 => mt_rand(-15, 12),
                    $roll <= 94 => mt_rand(16, 29),
                    default => mt_rand(31, 70),
                };

                $start = Carbon::parse($day->toDateString().' '.$employee->shift->starts_at, $employee->office->timezone);
                $end = Carbon::parse($day->toDateString().' '.$employee->shift->ends_at, $employee->office->timezone);
                if ($employee->shift->crossesMidnight()) {
                    $end->addDay();
                }

                try {
                    $registerAttendance->handle($employee, AttendanceChannel::KioskPin, [], $start->copy()->addMinutes($minutes));
                    $registerAttendance->handle($employee, AttendanceChannel::KioskPin, [], $end->copy()->addMinutes(mt_rand(-20, 25)));
                } catch (Throwable) {
                    // Días que no aplican al turno se ignoran
                }
            }
        }
    }
}
