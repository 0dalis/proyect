<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function __construct(private PushService $push) {}

    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $query = $company->notifications()
            ->with(['creator:id,email', 'area:id,name', 'office:id,name'])
            ->withCount('reads')
            ->orderByDesc('created_at');

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return response()->json(['notifications' => $query->get()]);
    }

    /**
     * Lista de empleados con su estado de acceso/instalación para notificaciones.
     */
    public function recipients(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $employees = $company->employees()
            ->with(['office:id,name', 'area:id,name', 'user:id,email,device_token'])
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) {
                $status = 'no_access';
                if ($employee->user_id) {
                    $status = ($employee->user && $employee->user->device_token) ? 'available' : 'access_not_installed';
                }

                return [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->full_name,
                    'employee_code' => $employee->employee_code,
                    'office' => $employee->office?->name,
                    'area' => $employee->area?->name,
                    'user_id' => $employee->user_id,
                    'email' => $employee->user?->email,
                    'has_device' => (bool) ($employee->user?->device_token),
                    'status' => $status,
                ];
            });

        return response()->json([
            'recipients' => $employees,
            'summary' => [
                'total' => $employees->count(),
                'no_access' => $employees->where('status', 'no_access')->count(),
                'access_not_installed' => $employees->where('status', 'access_not_installed')->count(),
                'available' => $employees->where('status', 'available')->count(),
            ],
        ]);
    }

    private function targetRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_type' => 'required|in:all,area,office,user,users',
            'area_id' => 'required_if:target_type,area|nullable|exists:areas,id',
            'office_id' => 'required_if:target_type,office|nullable|exists:offices,id',
            'target_user_id' => 'required_if:target_type,user|nullable|exists:users,id',
            'target_user_ids' => 'required_if:target_type,users|nullable|array',
            'target_user_ids.*' => 'integer|exists:users,id',
            'scheduled_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'priority' => 'nullable|in:normal,high,urgent',
            'is_active' => 'boolean',
        ];
    }

    private function buildFromRequest(Request $request): Notification
    {
        return new Notification($request->only([
            'target_type', 'area_id', 'office_id', 'target_user_id', 'target_user_ids',
        ]));
    }

    public function preview(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'target_type' => 'required|in:all,area,office,user,users',
            'area_id' => 'required_if:target_type,area|nullable|exists:areas,id',
            'office_id' => 'required_if:target_type,office|nullable|exists:offices,id',
            'target_user_id' => 'required_if:target_type,user|nullable|exists:users,id',
            'target_user_ids' => 'required_if:target_type,users|nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification = $this->buildFromRequest($request);
        $users = $notification->recipientUsers($company);

        $reachable = $users->filter(fn ($u) => ! empty($u->device_token));
        $skipped = $users->filter(fn ($u) => empty($u->device_token));

        return response()->json([
            'reachable' => $reachable->map(fn ($u) => ['id' => $u->id, 'email' => $u->email])->values(),
            'skipped' => $skipped->map(fn ($u) => ['id' => $u->id, 'email' => $u->email])->values(),
            'counts' => [
                'reachable' => $reachable->count(),
                'skipped' => $skipped->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), $this->targetRules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification = $company->notifications()->create(array_merge(
            $request->only([
                'title', 'message', 'target_type', 'area_id', 'office_id',
                'target_user_id', 'target_user_ids', 'scheduled_at', 'expires_at',
            ]),
            [
                'created_by' => $request->user()->id,
                'priority' => $request->input('priority', 'normal'),
                'is_active' => $request->boolean('is_active', true),
            ]
        ));

        return response()->json(['message' => 'Notificación creada.', 'notification' => $notification], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $notification = $company->notifications()->findOrFail($id);

        $validator = Validator::make($request->all(), $this->targetRules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification->update($request->only([
            'title', 'message', 'target_type', 'area_id', 'office_id',
            'target_user_id', 'target_user_ids', 'scheduled_at', 'expires_at', 'priority', 'is_active',
        ]));

        return response()->json(['message' => 'Notificación actualizada.', 'notification' => $notification]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $notification = $company->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notificación eliminada.']);
    }

    public function send(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $notification = $company->notifications()->findOrFail($id);

        $users = $notification->recipientUsers($company);
        $reachable = $users->filter(fn ($u) => ! empty($u->device_token));
        $skipped = $users->filter(fn ($u) => empty($u->device_token));

        $sent = $this->push->send(
            $reachable->pluck('device_token')->all(),
            $notification->title,
            $notification->message,
            ['notification_id' => (string) $notification->id]
        );

        $notification->update([
            'sent_at' => now(),
            'is_active' => true,
            'sent_count' => $sent,
            'failed_count' => max(0, $reachable->count() - $sent),
        ]);

        return response()->json([
            'message' => 'Notificación enviada.',
            'push_sent' => $sent,
            'skipped' => $skipped->count(),
            'notification' => $notification,
        ]);
    }

    public function read(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $notification = $company->notifications()->findOrFail($id);

        NotificationRead::updateOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $request->user()->id],
            ['company_id' => $company->id, 'read_at' => now()]
        );

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }
}
