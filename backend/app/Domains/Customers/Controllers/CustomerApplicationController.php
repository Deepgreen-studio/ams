<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexCustomerApplicationRequest;
use App\Domains\Customers\Requests\StoreCustomerApplicationRequest;
use App\Domains\Customers\Requests\UpdateCustomerApplicationRequest;
use App\Domains\Customers\Resources\CustomerApplicationCollection;
use App\Domains\Customers\Resources\CustomerApplicationResource;
use App\Domains\Customers\Services\CustomerApplicationService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerApplicationController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerApplicationService $customerApplicationService
    ) {}

    public function index(IndexCustomerApplicationRequest $request): JsonResponse
    {
        $this->authorize('viewApplications', Customer::class);

        $assignments = $this->customerApplicationService->list($request->filters());

        return ApiResponse::success([
            'assignments' => (new CustomerApplicationCollection($assignments))->resolve(),
        ]);
    }

    public function history(IndexCustomerApplicationRequest $request): JsonResponse
    {
        $this->authorize('viewApplications', Customer::class);

        $assignments = $this->customerApplicationService->history($request->filters());

        return ApiResponse::success([
            'history' => (new CustomerApplicationCollection($assignments))->resolve(),
        ]);
    }

    public function store(StoreCustomerApplicationRequest $request): JsonResponse
    {
        $this->authorize('manageApplications', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $assignment = $this->customerApplicationService->create($request->validated(), $actor);

        return ApiResponse::success([
            'assignment' => new CustomerApplicationResource($assignment),
        ], 'Application assigned to customer successfully.', 201);
    }

    public function show(string $assignment): JsonResponse
    {
        $model = $this->customerApplicationService->show($assignment);
        $this->authorize('viewApplicationAssignment', $model);

        return ApiResponse::success([
            'assignment' => new CustomerApplicationResource($model),
        ]);
    }

    public function update(UpdateCustomerApplicationRequest $request, string $assignment): JsonResponse
    {
        $existing = $this->customerApplicationService->find($assignment);
        $this->authorize('updateApplicationAssignment', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->customerApplicationService->update($assignment, $request->validated(), $actor);

        return ApiResponse::success([
            'assignment' => new CustomerApplicationResource($updated),
        ], 'Customer application assignment updated successfully.');
    }

    public function destroy(Request $request, string $assignment): JsonResponse
    {
        $existing = $this->customerApplicationService->find($assignment);
        $this->authorize('deleteApplicationAssignment', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->customerApplicationService->delete($assignment, $actor);

        return ApiResponse::success(null, 'Customer application assignment archived successfully.');
    }

    public function restore(Request $request, string $assignment): JsonResponse
    {
        $existing = $this->customerApplicationService->find($assignment, withTrashed: true);
        $this->authorize('restoreApplicationAssignment', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->customerApplicationService->restore($assignment, $actor);

        return ApiResponse::success([
            'assignment' => new CustomerApplicationResource($restored),
        ], 'Customer application assignment restored successfully.');
    }

    public function timeline(Request $request, string $assignment): JsonResponse
    {
        $existing = $this->customerApplicationService->find($assignment);
        $this->authorize('viewApplicationAssignment', $existing);

        $limit = max(1, min((int) $request->query('limit', 50), 100));

        return ApiResponse::success([
            'timeline' => $this->customerApplicationService->timeline($assignment, $limit),
        ]);
    }
}
