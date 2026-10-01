<?php

namespace App\Filament\Widgets;

use App\Support\PlatformMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemHealth extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Salud del sistema';

    protected function getStats(): array
    {
        $health = app(PlatformMetrics::class)->systemHealth();
        $diskLow = (int) $health['disk_free_percent'] < 15;

        return [
            Stat::make('Servidor', "PHP {$health['php']}")
                ->description("Laravel {$health['laravel']} · MySQL {$health['mysql']}")
                ->descriptionIcon(Heroicon::ServerStack),
            Stat::make('Cola de trabajos', $health['pending_jobs'].' pendientes')
                ->description("{$health['failed_jobs']} fallidos · driver {$health['queue']}")
                ->descriptionIcon((int) $health['failed_jobs'] > 0 ? Heroicon::ExclamationTriangle : Heroicon::CheckCircle)
                ->color((int) $health['failed_jobs'] > 0 ? 'danger' : 'success'),
            Stat::make('Disco', $health['disk_free_percent'].'% libre')
                ->description($health['disk_free'])
                ->descriptionIcon($diskLow ? Heroicon::ExclamationTriangle : Heroicon::CheckCircle)
                ->color($diskLow ? 'danger' : 'success'),
            Stat::make('Integraciones', 'Correo: '.$health['mail'])
                ->description('Stripe '.$health['stripe'])
                ->descriptionIcon($health['stripe'] === 'configurado' ? Heroicon::CheckCircle : Heroicon::ExclamationTriangle)
                ->color($health['stripe'] === 'configurado' ? 'success' : 'warning'),
        ];
    }
}
