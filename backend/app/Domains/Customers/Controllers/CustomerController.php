<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexCustomerRequest;
use App\Domains\Customers\Requests\StoreCustomerRequest;
use App\Domains\Customers\Requests\UpdateCustomerRequest;
use App\Domains\Customers\Resources\CustomerCollection;
use App\Domains\Customers\Resources\CustomerResource;
use App\Domains\Customers\Services\CustomerService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    public function index(IndexCustomerRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $result = $this->customerService->list($request->filters());

        return ApiResponse::success([
            'customers' => (new CustomerCollection($result['customers']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $customer = $this->customerService->create($request->validated(), $actor);

        return ApiResponse::success([
            'customer' => new CustomerResource($customer),
        ], 'Customer created successfully.', 201);
    }

    public function show(string $customer): JsonResponse
    {
        $model = $this->customerService->show($customer);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'customer' => new CustomerResource($model),
        ]);
    }

    public function update(UpdateCustomerRequest $request, string $customer): JsonResponse
    {
        $existing = $this->customerService->find($customer);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->customerService->update($customer, $request->validated(), $actor);

        return ApiResponse::success([
            'customer' => new CustomerResource($updated),
        ], 'Customer updated successfully.');
    }

    public function destroy(Request $request, string $customer): JsonResponse
    {
        $existing = $this->customerService->find($customer);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->customerService->delete($customer, $actor);

        return ApiResponse::success(null, 'Customer archived successfully.');
    }

    public function restore(Request $request, string $customer): JsonResponse
    {
        $existing = $this->customerService->find($customer, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->customerService->restore($customer, $actor);

        return ApiResponse::success([
            'customer' => new CustomerResource($restored),
        ], 'Customer restored successfully.');
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $company = $request->query('company') ?? $request->query('company_id');

        return ApiResponse::success([
            'statistics' => $this->customerService->statistics(
                is_string($company) || is_numeric($company) ? (string) $company : null
            ),
        ]);
    }
}
