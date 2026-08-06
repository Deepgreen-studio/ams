<?php

namespace App\Domains\Support\Controllers;

use App\Domains\Support\Requests\StoreSupportSlaCalendarRequest;
use App\Domains\Support\Requests\StoreSupportSlaHolidayRequest;
use App\Domains\Support\Requests\StoreSupportSlaPolicyRequest;
use App\Domains\Support\Requests\UpdateSupportSlaCalendarRequest;
use App\Domains\Support\Requests\UpdateSupportSlaHolidayRequest;
use App\Domains\Support\Requests\UpdateSupportSlaPolicyRequest;
use App\Domains\Support\Resources\SupportSlaCalendarResource;
use App\Domains\Support\Resources\SupportSlaEscalationResource;
use App\Domains\Support\Resources\SupportSlaHolidayResource;
use App\Domains\Support\Resources\SupportSlaPolicyResource;
use App\Domains\Support\Resources\SupportSlaTimerResource;
use App\Domains\Support\Resources\SupportTicketResource;
use App\Domains\Support\Services\SupportSlaService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportSlaController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SupportSlaService $slaService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->slaService->dashboard($request->query());

        return ApiResponse::success([
            'statistics' => $data['statistics'],
            'by_status' => $data['by_status'],
            'timers' => SupportSlaTimerResource::collection($data['timers'])->resolve(),
        ]);
    }

    public function escalationQueue(Request $request): JsonResponse
    {
        $paginator = $this->slaService->escalationQueue($request->query());

        return ApiResponse::success([
            'escalations' => [
                'items' => SupportSlaEscalationResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function violations(Request $request): JsonResponse
    {
        $report = $this->slaService->violationReport($request->query());
        $paginator = $report['items'];

        return ApiResponse::success([
            'summary' => $report['summary'],
            'violations' => [
                'items' => SupportTicketResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function policies(Request $request): JsonResponse
    {
        $paginator = $this->slaService->listPolicies($request->query());

        return ApiResponse::success([
            'policies' => [
                'items' => SupportSlaPolicyResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function showPolicy(string $policy): JsonResponse
    {
        $model = $this->slaService->findPolicy($policy);

        return ApiResponse::success([
            'policy' => new SupportSlaPolicyResource($model),
        ]);
    }

    public function storePolicy(StoreSupportSlaPolicyRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $policy = $this->slaService->createPolicy($request->validated(), $actor);

        return ApiResponse::success([
            'policy' => new SupportSlaPolicyResource($policy),
        ], 'SLA policy created successfully.', 201);
    }

    public function updatePolicy(UpdateSupportSlaPolicyRequest $request, string $policy): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->slaService->updatePolicy($policy, $request->validated(), $actor);

        return ApiResponse::success([
            'policy' => new SupportSlaPolicyResource($updated),
        ], 'SLA policy updated successfully.');
    }

    public function destroyPolicy(string $policy): JsonResponse
    {
        $this->slaService->deletePolicy($policy);

        return ApiResponse::success(null, 'SLA policy deleted successfully.');
    }

    public function calendars(Request $request): JsonResponse
    {
        $paginator = $this->slaService->listCalendars($request->query());

        return ApiResponse::success([
            'calendars' => [
                'items' => SupportSlaCalendarResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function storeCalendar(StoreSupportSlaCalendarRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $calendar = $this->slaService->createCalendar($request->validated(), $actor);

        return ApiResponse::success([
            'calendar' => new SupportSlaCalendarResource($calendar),
        ], 'Business hours calendar created successfully.', 201);
    }

    public function updateCalendar(UpdateSupportSlaCalendarRequest $request, string $calendar): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->slaService->updateCalendar($calendar, $request->validated(), $actor);

        return ApiResponse::success([
            'calendar' => new SupportSlaCalendarResource($updated),
        ], 'Business hours calendar updated successfully.');
    }

    public function holidays(Request $request): JsonResponse
    {
        $paginator = $this->slaService->listHolidays($request->query());

        return ApiResponse::success([
            'holidays' => [
                'items' => SupportSlaHolidayResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function storeHoliday(StoreSupportSlaHolidayRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $holiday = $this->slaService->createHoliday($request->validated(), $actor);

        return ApiResponse::success([
            'holiday' => new SupportSlaHolidayResource($holiday->load(['company', 'calendar'])),
        ], 'Holiday created successfully.', 201);
    }

    public function updateHoliday(UpdateSupportSlaHolidayRequest $request, string $holiday): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->slaService->updateHoliday($holiday, $request->validated(), $actor);

        return ApiResponse::success([
            'holiday' => new SupportSlaHolidayResource($updated->load(['company', 'calendar'])),
        ], 'Holiday updated successfully.');
    }

    public function destroyHoliday(string $holiday): JsonResponse
    {
        $this->slaService->deleteHoliday($holiday);

        return ApiResponse::success(null, 'Holiday deleted successfully.');
    }

    public function acknowledgeEscalation(Request $request, string $escalation): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->slaService->acknowledgeEscalation(
            $escalation,
            $actor,
            $request->input('notes')
        );

        return ApiResponse::success([
            'escalation' => new SupportSlaEscalationResource($model->load([
                'ticket.company', 'ticket.assignee', 'policy', 'assignee', 'acknowledger',
            ])),
        ], 'Escalation acknowledged.');
    }

    public function resolveEscalation(Request $request, string $escalation): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->slaService->resolveEscalation(
            $escalation,
            $actor,
            $request->input('notes')
        );

        return ApiResponse::success([
            'escalation' => new SupportSlaEscalationResource($model->load([
                'ticket.company', 'ticket.assignee', 'policy', 'assignee', 'acknowledger',
            ])),
        ], 'Escalation resolved.');
    }

    public function evaluate(): JsonResponse
    {
        $count = $this->slaService->evaluate();

        return ApiResponse::success([
            'evaluated' => $count,
        ], 'SLA evaluation completed.');
    }
}
