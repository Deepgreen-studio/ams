<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexCustomerCommunicationRequest;
use App\Domains\Customers\Requests\StoreCustomerCommunicationRequest;
use App\Domains\Customers\Requests\UpdateCustomerCommunicationRequest;
use App\Domains\Customers\Resources\CustomerCommunicationCollection;
use App\Domains\Customers\Resources\CustomerCommunicationResource;
use App\Domains\Customers\Services\CustomerCommunicationService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerCommunicationController
{
    use AuthorizesRequests;

    public function __construct(private readonly CustomerCommunicationService $communicationService) {}

    public function index(IndexCustomerCommunicationRequest $request): JsonResponse
    {
        $this->authorize('viewCommunications', Customer::class);
        $result = $this->communicationService->list($request->filters());

        return ApiResponse::success([
            'communications' => (new CustomerCommunicationCollection($result['communications']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function store(StoreCustomerCommunicationRequest $request): JsonResponse
    {
        $this->authorize('manageCommunications', Customer::class);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'communication' => new CustomerCommunicationResource(
                $this->communicationService->create($request->validated(), $actor)
            ),
        ], 'Communication logged successfully.', 201);
    }

    public function show(string $communication): JsonResponse
    {
        $model = $this->communicationService->show($communication);
        $this->authorize('viewCommunication', $model);

        return ApiResponse::success(['communication' => new CustomerCommunicationResource($model)]);
    }

    public function update(UpdateCustomerCommunicationRequest $request, string $communication): JsonResponse
    {
        $existing = $this->communicationService->find($communication);
        $this->authorize('updateCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'communication' => new CustomerCommunicationResource(
                $this->communicationService->update($communication, $request->validated(), $actor)
            ),
        ], 'Communication updated successfully.');
    }

    public function destroy(Request $request, string $communication): JsonResponse
    {
        $existing = $this->communicationService->find($communication);
        $this->authorize('deleteCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();
        $this->communicationService->delete($communication, $actor);

        return ApiResponse::success(null, 'Communication archived successfully.');
    }

    public function restore(Request $request, string $communication): JsonResponse
    {
        $existing = $this->communicationService->find($communication, withTrashed: true);
        $this->authorize('restoreCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'communication' => new CustomerCommunicationResource(
                $this->communicationService->restore($communication, $actor)
            ),
        ], 'Communication restored successfully.');
    }
}
