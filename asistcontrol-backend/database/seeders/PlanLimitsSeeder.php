<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanLimitsSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'id' => 1,
                'max_offices' => 1,
                'annual_price' => 0,
                'per_extra_user_price' => null,
                'per_extra_office_price' => null,
                'features' => [
                    'attendance' => true,
                    'bonus_calculation' => false,
                    'salary_calculation' => false,
                    'vacations' => false,
                    'geofencing' => false,
                    'photo_checkin' => false,
                    'shifts' => false,
                    'reports_csv' => true,
                    'reports_pdf' => false,
                    'dashboard' => false,
                    'mobile_app' => false,
                    'api' => false,
                    'white_label' => false,
                    'support' => 'correo 72h',
                ],
            ],
            [
                'id' => 2,
                'max_offices' => 3,
                'annual_price' => 349 * 11,
                'per_extra_user_price' => 20,
                'per_extra_office_price' => 50,
                'features' => [
                    'attendance' => true,
                    'bonus_calculation' => false,
                    'salary_calculation' => false,
                    'vacations' => false,
                    'geofencing' => true,
                    'photo_checkin' => true,
                    'shifts' => true,
                    'reports_csv' => true,
                    'reports_pdf' => true,
                    'dashboard' => false,
                    'mobile_app' => true,
                    'api' => false,
                    'white_label' => false,
                    'support' => 'correo 24h',
                ],
            ],
            [
                'id' => 3,
                'max_offices' => 10,
                'annual_price' => 599 * 11,
                'per_extra_user_price' => 30,
                'per_extra_office_price' => 70,
                'features' => [
                    'attendance' => true,
                    'bonus_calculation' => true,
                    'salary_calculation' => true,
                    'vacations' => true,
                    'geofencing' => true,
                    'photo_checkin' => true,
                    'shifts' => true,
                    'reports_csv' => true,
                    'reports_pdf' => true,
                    'dashboard' => true,
                    'mobile_app' => true,
                    'api' => true,
                    'white_label' => false,
                    'support' => 'chat 4h',
                ],
            ],
            [
                'id' => 4,
                'max_offices' => null,
                'annual_price' => 799 * 11,
                'per_extra_user_price' => 40,
                'per_extra_office_price' => 100,
                'features' => [
                    'attendance' => true,
                    'bonus_calculation' => true,
                    'salary_calculation' => true,
                    'vacations' => true,
                    'geofencing' => true,
                    'photo_checkin' => true,
                    'shifts' => true,
                    'reports_csv' => true,
                    'reports_pdf' => true,
                    'dashboard' => true,
                    'mobile_app' => true,
                    'api' => true,
                    'white_label' => true,
                    'support' => 'teléfono 1h',
                ],
            ],
        ];

        foreach ($plans as $data) {
            $id = $data['id'];
            unset($data['id']);

            Plan::query()->whereKey($id)->update($data);
        }

        $this->command->info('Límites de planes actualizados correctamente.');
    }
}
