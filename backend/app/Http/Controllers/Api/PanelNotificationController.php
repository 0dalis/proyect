<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\PanelNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Campana del menú superior: cada usuario ve y marca solo las suyas.
 */
class PanelNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = PanelNotification::query()->for($user)
            ->when($request->boolean('unread'), fn ($query) => $query->unread())
            ->latest('id')
            ->paginate(min(50, $request->integer('per_page', 15)));

        return response()->json([
            ...$notifications->toArray(),
            'unread' => PanelNotification::query()->for($user)->unread()->count(),
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        return response()->json(['unread' => PanelNotification::query()->for($request->user())->unread()->count()]);
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $item = PanelNotification::query()->for($request->user())->findOrFail($notification);

        if (! $item->read_at) {
            $item->forceFill(['read_at' => now()])->save();
            $this->markAnnouncementRead($request, $item);
        }

        return response()->json(['unread' => PanelNotification::query()->for($request->user())->unread()->count()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $query = PanelNotification::query()->for($request->user())->unread();

        $query->clone()->where('subject_type', 'announcement')->get()
            ->each(fn (PanelNotification $item) => $this->markAnnouncementRead($request, $item));
        $query->update(['read_at' => now()]);

        return response()->json(['unread' => 0]);
    }

    /**
     * Un aviso leído desde la campana también queda leído en "Avisos".
     */
    private function markAnnouncementRead(Request $request, PanelNotification $item): void
    {
        $employeeId = $request->user()->employee_id;

        if ($item->subject_type === 'announcement' && $employeeId) {
            Announcement::query()->find($item->subject_id)?->recipients()
                ->updateExistingPivot($employeeId, ['read_at' => now()]);
        }
    }
}
