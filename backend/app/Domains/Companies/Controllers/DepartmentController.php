<?php

namespace App\Domains\Companies\Controllers;

use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Requests\StoreDepartmentRequest;
use App\Domains\Companies\Requests\UpdateDepartmentRequest;
use App\Domains\Companies\Repositories\DepartmentRepository;
use App\Domains\Companies\Resources\DepartmentResource;
use App\Domains\Companies\Services\DepartmentService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DepartmentService $departmentService,
        private readonly DepartmentRepository $departmentRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewDepartments', Company::class);

        $departments = $this->departmentService->list($request->only([
            'company', 'search', 'status', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'departments' => [
                'items' => DepartmentResource::collection($departments->items()),
                'meta' => [
                    'current_page' => $departments->currentPage(),
                    'last_page' => $departments->lastPage(),
                    'per_page' => $departments->perPage(),
                    'total' => $departments->total(),
                ],
            ],
        ]);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('manageDepartments', Company::class);

        /** @var User $actor */
        $actor = $request->user();
        $department = $this->departmentService->create($request->validated(), $actor);

        return ApiResponse::success([
            'department' => new DepartmentResource($department),
        ], 'Department created successfully.', 201);
    }

    public function update(UpdateDepartmentRequest $request, string $department): JsonResponse
    {
        $existing = $this->departmentRepository->findByIdentifierOrFail($department);
        $this->authorize('updateDepartment', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->departmentService->update($department, $request->validated(), $actor);

        return ApiResponse::success([
            'department' => new DepartmentResource($updated),
        ], 'Department updated successfully.');
    }

    public function destroy(Request $request, string $department): JsonResponse
    {
        $existing = $this->departmentRepository->findByIdentifierOrFail($department);
        $this->authorize('deleteDepartment', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->departmentService->delete($department, $actor);

        return ApiResponse::success(null, 'Department deleted successfully.');
    }
}
