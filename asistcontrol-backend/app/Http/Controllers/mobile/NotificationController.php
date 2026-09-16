<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $areaId = $user->employee?->area_id;
        $officeId = $user->employee?->office_id;

        $notifications = Notification::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->where(function ($query) use ($user, $areaId, $officeId) {
                $query->where('target_type', 'all')
                    ->orWhere(function ($q) use ($areaId) {
                        $q->where('target_type', 'area')->where('area_id', $areaId);
                    })
                    ->orWhere(function ($q) use ($officeId) {
                        $q->where('target_type', 'office')->where('office_id', $officeId);
                    })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('target_type', 'user')->where('target_user_id', $user->id);
                    })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('target_type', 'users')->whereJsonContains('target_user_ids', $user->id);
                    });
            })
            ->orderByDesc('created_at')
            ->get();

        $readIds = NotificationRead::where('user_id', $user->id)->pluck('notification_id')->all();

        $notifications->each(function ($notification) use ($readIds) {
            $notification->is_read = in_array($notification->id, $readIds, true);
        });

        return response()->json(['notifications' => $notifications]);
    }

    public function read(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        NotificationRead::updateOrCreate(
            ['notification_id' => $id, 'user_id' => $user->id],
            ['company_id' => $user->company_id, 'read_at' => now()]
        );

        return response()->json(['message' => 'Notificación leída.']);
    }
}
