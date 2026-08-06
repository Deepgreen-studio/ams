<?php

namespace App\Domains\Compliance\Controllers;

use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Compliance\Requests\CompletePrivacyRequestRequest;
use App\Domains\Compliance\Requests\ConfirmPrivacyDeletionRequest;
use App\Domains\Compliance\Requests\DecidePrivacyRequestRequest;
use App\Domains\Compliance\Requests\RejectPrivacyRequestRequest;
use App\Domains\Compliance\Requests\StorePrivacyRequestRequest;
use App\Domains\Compliance\Requests\UpdatePrivacyRequestRequest;
use App\Domains\Compliance\Requests\VerifyPrivacyRequestIdentityRequest;
use App\Domains\Compliance\Resources\PrivacyRequestCollection;
use App\Domains\Compliance\Resources\PrivacyRequestLogResource;
use App\Domains\Compliance\Resources\PrivacyRequestResource;
use App\Domains\Compliance\Services\PrivacyRequestService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyRequestController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PrivacyRequestService $privacyRequestService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PrivacyRequest::class);

        $result = $this->privacyRequestService->dashboard($request->query('company'));

        return ApiResponse::success([
            'statistics' => $result['statistics'],
            'recent_active' => PrivacyRequestResource::collection($result['recent_active'])->resolve(),
            'awaiting_verification' => PrivacyRequestResource::collection($result['awaiting_verification'])->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PrivacyRequest::class);

        $requests = $this->privacyRequestService->list($request->only([
            'search',
            'status',
            'request_type',
            'identity_verification_status',
            'decision',
            'company',
            'company_id',
            'assigned_to',
            'assignee',
            'overdue',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'privacy_requests' => (new PrivacyRequestCollection($requests))->resolve(),
        ]);
    }

    public function store(StorePrivacyRequestRequest $request): JsonResponse
    {
        $this->authorize('create', PrivacyRequest::class);

        /** @var User $actor */
        $actor = $request->user();
        $privacyRequest = $this->privacyRequestService->create($request->validated(), $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($privacyRequest),
        ], 'Privacy request created successfully.', 201);
    }

    public function show(string $privacyRequest): JsonResponse
    {
        $model = $this->privacyRequestService->show($privacyRequest);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($model),
        ]);
    }

    public function update(UpdatePrivacyRequestRequest $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->privacyRequestService->update($privacyRequest, $request->validated(), $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($updated),
        ], 'Privacy request updated successfully.');
    }

    public function destroy(Request $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->privacyRequestService->delete($privacyRequest, $actor);

        return ApiResponse::success(null, 'Privacy request deleted successfully.');
    }

    public function restore(Request $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->privacyRequestService->restore($privacyRequest, $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($restored),
        ], 'Privacy request restored successfully.');
    }

    public function timeline(string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('view', $existing);

        $logs = $this->privacyRequestService->timeline($privacyRequest);

        return ApiResponse::success([
            'timeline' => PrivacyRequestLogResource::collection($logs)->resolve(),
        ]);
    }

    public function verifyIdentity(VerifyPrivacyRequestIdentityRequest $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('verify', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->privacyRequestService->verifyIdentity($privacyRequest, $request->validated(), $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($updated),
        ], 'Identity verification updated successfully.');
    }

    public function approve(DecidePrivacyRequestRequest $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('decide', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->privacyRequestService->approve($privacyRequest, $request->validated(), $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($updated),
        ], 'Privacy request approved successfully.');
    }

    public function reject(RejectPrivacyRequestRequest $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('decide', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->privacyRequestService->reject($privacyRequest, $request->validated(), $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($updated),
        ], 'Privacy request rejected successfully.');
    }

    public function export(Request $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('export', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->privacyRequestService->generateExport($privacyRequest, $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($updated),
        ], 'Subject data export generated successfully.');
    }

    public function downloadExport(string $privacyRequest): StreamedResponse|JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('export', $existing);

        if (blank($existing->export_file_path) || ! Storage::disk('local')->exists($existing->export_file_path)) {
            return ApiResponse::error('Export file not found. Generate the export first.', 404);
        }

        return Storage::disk('local')->download(
            $existing->export_file_path,
            $existing->request_number.'-export.json'
        );
    }

    public function confirmDeletion(ConfirmPrivacyDeletionRequest $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->privacyRequestService->confirmDeletion($privacyRequest, $request->validated(), $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($updated),
        ], 'Data deletion confirmed successfully.');
    }

    public function complete(CompletePrivacyRequestRequest $request, string $privacyRequest): JsonResponse
    {
        $existing = $this->privacyRequestService->find($privacyRequest);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->privacyRequestService->complete($privacyRequest, $request->validated(), $actor);

        return ApiResponse::success([
            'privacy_request' => new PrivacyRequestResource($updated),
        ], 'Privacy request completed successfully.');
    }
}
