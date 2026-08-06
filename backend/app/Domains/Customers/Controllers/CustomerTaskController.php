<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexCustomerTaskRequest;
use App\Domains\Customers\Requests\StoreCustomerTaskRequest;
use App\Domains\Customers\Requests\UpdateCustomerTaskRequest;
use App\Domains\Customers\Resources\CustomerTaskCollection;
use App\Domains\Customers\Resources\CustomerTaskResource;
use App\Domains\Customers\Services\CustomerTaskService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerTaskController
{
    use AuthorizesRequests;

    public function __construct(private readonly CustomerTaskService $taskService) {}

    public function index(IndexCustomerTaskRequest $request): JsonResponse
    {
        $this->authorize('viewCommunications', Customer::class);
        $result = $this->taskService->list($request->filters());

        return ApiResponse::success([
            'tasks' => (new CustomerTaskCollection($result['tasks']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $this->authorize('viewCommunications', Customer::class);
        $customer = $request->query('customer') ?? $request->query('customer_id');

        return ApiResponse::success([
            'reminders' => CustomerTaskResource::collection(
                $this->taskService->calendar(
                    is_string($customer) || is_numeric($customer) ? (string) $customer : null,
                    $request->query('from'),
                    $request->query('to')
                )
            )->resolve(),
        ]);
    }

    public function store(StoreCustomerTaskRequest $request): JsonResponse
    {
        $this->authorize('manageCommunications', Customer::class);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'task' => new CustomerTaskResource($this->taskService->create($request->validated(), $actor)),
        ], 'Task created successfully.', 201);
    }

    public function show(string $task): JsonResponse
    {
        $model = $this->taskService->show($task);
        $this->authorize('viewCommunication', $model);

        return ApiResponse::success(['task' => new CustomerTaskResource($model)]);
    }

    public function update(UpdateCustomerTaskRequest $request, string $task): JsonResponse
    {
        $existing = $this->taskService->find($task);
        $this->authorize('updateCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'task' => new CustomerTaskResource($this->taskService->update($task, $request->validated(), $actor)),
        ], 'Task updated successfully.');
    }

    public function complete(Request $request, string $task): JsonResponse
    {
        $existing = $this->taskService->find($task);
        $this->authorize('updateCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'task' => new CustomerTaskResource($this->taskService->complete($task, $actor)),
        ], 'Task completed successfully.');
    }

    public function destroy(Request $request, string $task): JsonResponse
    {
        $existing = $this->taskService->find($task);
        $this->authorize('deleteCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();
        $this->taskService->delete($task, $actor);

        return ApiResponse::success(null, 'Task archived successfully.');
    }

    public function restore(Request $request, string $task): JsonResponse
    {
        $existing = $this->taskService->find($task, withTrashed: true);
        $this->authorize('restoreCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'task' => new CustomerTaskResource($this->taskService->restore($task, $actor)),
        ], 'Task restored successfully.');
    }
}
