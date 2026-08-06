<?php

namespace App\Domains\Compliance\Controllers;

use App\Domains\Compliance\Models\DataBreach;
use App\Domains\Compliance\Requests\AssessDataBreachRiskRequest;
use App\Domains\Compliance\Requests\CloseDataBreachRequest;
use App\Domains\Compliance\Requests\ContainDataBreachRequest;
use App\Domains\Compliance\Requests\LessonsLearnedDataBreachRequest;
use App\Domains\Compliance\Requests\RecoverDataBreachRequest;
use App\Domains\Compliance\Requests\RootCauseDataBreachRequest;
use App\Domains\Compliance\Requests\SendBreachNotificationRequest;
use App\Domains\Compliance\Requests\StoreBreachActionRequest;
use App\Domains\Compliance\Requests\StoreBreachNotificationRequest;
use App\Domains\Compliance\Requests\StoreDataBreachRequest;
use App\Domains\Compliance\Requests\UpdateAffectedUsersRequest;
use App\Domains\Compliance\Requests\UpdateDataBreachRequest;
use App\Domains\Compliance\Resources\BreachActionResource;
use App\Domains\Compliance\Resources\BreachNotificationCollection;
use App\Domains\Compliance\Resources\BreachNotificationResource;
use App\Domains\Compliance\Resources\DataBreachCollection;
use App\Domains\Compliance\Resources\DataBreachResource;
use App\Domains\Compliance\Services\DataBreachService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataBreachController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DataBreachService $dataBreachService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DataBreach::class);

        $result = $this->dataBreachService->dashboard($request->query('company'));

        return ApiResponse::success([
            'statistics' => $result['statistics'],
            'recent_active' => DataBreachResource::collection($result['recent_active'])->resolve(),
            'regulator_queue' => DataBreachResource::collection($result['regulator_queue'])->resolve(),
        ]);
    }

    public function riskMatrix(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DataBreach::class);

        return ApiResponse::success(
            $this->dataBreachService->riskMatrix($request->query('company'))
        );
    }

    public function reports(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DataBreach::class);

        return ApiResponse::success(
            $this->dataBreachService->reports($request->query('company'))
        );
    }

    public function notificationCenter(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DataBreach::class);

        $notifications = $this->dataBreachService->notificationCenter($request->only([
            'search',
            'status',
            'notification_type',
            'channel',
            'company',
            'company_id',
            'per_page',
            'page',
        ]));

        return ApiResponse::success([
            'notifications' => (new BreachNotificationCollection($notifications))->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DataBreach::class);

        $breaches = $this->dataBreachService->list($request->only([
            'search',
            'status',
            'severity',
            'breach_type',
            'risk_level',
            'company',
            'company_id',
            'assigned_to',
            'assignee',
            'regulator_overdue',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'breaches' => (new DataBreachCollection($breaches))->resolve(),
        ]);
    }

    public function store(StoreDataBreachRequest $request): JsonResponse
    {
        $this->authorize('create', DataBreach::class);

        /** @var User $actor */
        $actor = $request->user();
        $breach = $this->dataBreachService->create($request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($breach),
        ], 'Data breach reported successfully.', 201);
    }

    public function show(string $breach): JsonResponse
    {
        $model = $this->dataBreachService->show($breach);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'breach' => new DataBreachResource($model),
        ]);
    }

    public function update(UpdateDataBreachRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dataBreachService->update($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($updated),
        ], 'Data breach updated successfully.');
    }

    public function destroy(Request $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->dataBreachService->delete($breach, $actor);

        return ApiResponse::success(null, 'Data breach deleted successfully.');
    }

    public function restore(Request $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->dataBreachService->restore($breach, $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($restored),
        ], 'Data breach restored successfully.');
    }

    public function timeline(string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('view', $existing);

        $timeline = $this->dataBreachService->timeline($breach);

        return ApiResponse::success([
            'timeline' => BreachActionResource::collection($timeline)->resolve(),
        ]);
    }

    public function assess(AssessDataBreachRiskRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('assess', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dataBreachService->assessRisk($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($updated),
        ], 'Risk assessment recorded successfully.');
    }

    public function contain(ContainDataBreachRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('contain', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dataBreachService->contain($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($updated),
        ], 'Containment recorded successfully.');
    }

    public function recover(RecoverDataBreachRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dataBreachService->recover($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($updated),
        ], 'Recovery actions recorded successfully.');
    }

    public function rootCause(RootCauseDataBreachRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dataBreachService->recordRootCause($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($updated),
        ], 'Root cause analysis recorded successfully.');
    }

    public function lessonsLearned(LessonsLearnedDataBreachRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dataBreachService->recordLessonsLearned($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($updated),
        ], 'Lessons learned recorded successfully.');
    }

    public function affectedUsers(UpdateAffectedUsersRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dataBreachService->updateAffectedUsers($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($updated),
        ], 'Affected users updated successfully.');
    }

    public function close(CloseDataBreachRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('close', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dataBreachService->close($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'breach' => new DataBreachResource($updated),
        ], 'Data breach closed successfully.');
    }

    public function storeAction(StoreBreachActionRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $action = $this->dataBreachService->addAction($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'action' => new BreachActionResource($action),
        ], 'Breach action recorded successfully.', 201);
    }

    public function storeNotification(StoreBreachNotificationRequest $request, string $breach): JsonResponse
    {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('notify', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $notification = $this->dataBreachService->createNotification($breach, $request->validated(), $actor);

        return ApiResponse::success([
            'notification' => new BreachNotificationResource($notification),
        ], 'Breach notification created successfully.', 201);
    }

    public function sendNotification(
        SendBreachNotificationRequest $request,
        string $breach,
        string $notification
    ): JsonResponse {
        $existing = $this->dataBreachService->find($breach);
        $this->authorize('notify', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $sent = $this->dataBreachService->sendNotification(
            $breach,
            $notification,
            $actor,
            $request->validated()
        );

        return ApiResponse::success([
            'notification' => new BreachNotificationResource($sent),
        ], 'Breach notification sent successfully.');
    }
}
