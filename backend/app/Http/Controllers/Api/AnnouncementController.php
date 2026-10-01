<?php

namespace App\Http\Controllers\Api;

use App\Enums\AudienceType;
use App\Enums\EmployeeStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Jobs\SendAnnouncementPush;
use App\Models\Announcement;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Announcement::query()->withCount([
            'recipients',
            'recipients as read_count' => fn ($query) => $query->whereNotNull('announcement_employee.read_at'),
        ])->latest()->paginate(25));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'audience_type' => ['required', Rule::enum(AudienceType::class)],
            'audience_ids' => ['exclude_if:audience_type,all', 'required', 'array', 'min:1'],
            'audience_ids.*' => ['required'],
            'show_on_kiosk' => ['boolean'],
            'publish_at' => ['nullable', 'date', 'after:now'],
        ]);

        $announcement = Announcement::query()->create([...$data, 'author_user_id' => $request->user()->id]);

        $employeeIds = $this->resolveAudience($announcement, $request->user()->company_id);
        $announcement->recipients()->attach($employeeIds);

        SendAnnouncementPush::dispatch($announcement->company_id, $announcement->id)
            ->delay($announcement->publish_at);

        return response()->json([...$announcement->toArray(), 'recipients_count' => count($employeeIds)], 201);
    }

    /**
     * Bandeja de notificaciones del empleado en la app.
     */
    public function inbox(Request $request): JsonResponse
    {
        $employee = $request->user()->employee();

        if (! $employee) {
            return response()->json(['data' => []]);
        }

        return response()->json(
            $employee->announcements()
                ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
                ->latest()
                ->paginate(25)
        );
    }

    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        $announcement->recipients()->updateExistingPivot($request->user()->employee_id ?? 0, ['read_at' => now()]);

        return response()->json(['message' => 'Leída.']);
    }

    /**
     * "Noticias empresa": lo que también se muestra en el kiosko.
     */
    public function news(): JsonResponse
    {
        return response()->json(self::newsQuery()->limit(20)->get());
    }

    public static function newsQuery()
    {
        return Announcement::query()
            ->where('show_on_kiosk', true)
            ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where('created_at', '>=', now()->subDays(30))
            ->latest()
            ->select(['id', 'title', 'body', 'link_url', 'image_path', 'created_at']);
    }

    /**
     * @return list<int>
     */
    private function resolveAudience(Announcement $announcement, int $companyId): array
    {
        $employees = Employee::query()->where('status', EmployeeStatus::Active);
        $ids = $announcement->audience_ids ?? [];

        match ($announcement->audience_type) {
            AudienceType::All => null,
            AudienceType::Areas => $employees->whereIn('area_id', $ids),
            AudienceType::Offices => $employees->whereIn('office_id', $ids),
            AudienceType::Employees => $employees->whereIn('id', $ids),
            AudienceType::Roles => $employees->whereIn('id', User::query()
                ->where('company_id', $companyId)
                ->whereNotNull('employee_id')
                ->role(array_values(array_intersect($ids, array_column(Role::cases(), 'value'))))
                ->pluck('employee_id')),
        };

        return $employees->pluck('id')->all();
    }
}
