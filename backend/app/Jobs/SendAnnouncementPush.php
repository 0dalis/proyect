<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\Company;
use App\Models\Device;
use App\Support\PanelNotifier;
use App\Tenancy\TenantManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Envía la notificación push a los celulares de los destinatarios.
 * Mientras no haya credenciales de Firebase configuradas, solo se registra en el log.
 */
class SendAnnouncementPush implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $companyId, public int $announcementId) {}

    public function handle(TenantManager $tenants): void
    {
        $tenants->run(Company::query()->findOrFail($this->companyId), function () {
            $announcement = Announcement::query()->find($this->announcementId);

            if (! $announcement || $announcement->sent_at) {
                return;
            }

            $tokens = Device::query()
                ->whereIn('employee_id', $announcement->recipients()->pluck('employees.id'))
                ->whereNotNull('approved_at')
                ->whereNull('revoked_at')
                ->whereNotNull('push_token')
                ->pluck('push_token');

            // TODO: enviar con Firebase Cloud Messaging cuando se configure FIREBASE_CREDENTIALS
            Log::info('Push de aviso', [
                'announcement' => $announcement->id,
                'title' => $announcement->title,
                'devices' => $tokens->count(),
            ]);

            // Campana del panel de gerentes y empleados
            PanelNotifier::announcementPublished($announcement);

            $announcement->forceFill(['sent_at' => now()])->save();
        });
    }
}
