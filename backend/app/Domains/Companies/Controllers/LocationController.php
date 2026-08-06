<?php

namespace App\Domains\Companies\Controllers;

use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Requests\StoreLocationRequest;
use App\Domains\Companies\Requests\UpdateLocationRequest;
use App\Domains\Companies\Repositories\LocationRepository;
use App\Domains\Companies\Resources\LocationResource;
use App\Domains\Companies\Services\LocationService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly LocationService $locationService,
        private readonly LocationRepository $locationRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewLocations', Company::class);

        $locations = $this->locationService->list($request->only([
            'company', 'search', 'status', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'locations' => [
                'items' => LocationResource::collection($locations->items()),
                'meta' => [
                    'current_page' => $locations->currentPage(),
                    'last_page' => $locations->lastPage(),
                    'per_page' => $locations->perPage(),
                    'total' => $locations->total(),
                ],
            ],
        ]);
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $this->authorize('manageLocations', Company::class);

        /** @var User $actor */
        $actor = $request->user();
        $location = $this->locationService->create($request->validated(), $actor);

        return ApiResponse::success([
            'location' => new LocationResource($location),
        ], 'Location created successfully.', 201);
    }

    public function update(UpdateLocationRequest $request, string $companyLocation): JsonResponse
    {
        $existing = $this->locationRepository->findByIdentifierOrFail($companyLocation);
        $this->authorize('updateLocation', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->locationService->update($companyLocation, $request->validated(), $actor);

        return ApiResponse::success([
            'location' => new LocationResource($updated),
        ], 'Location updated successfully.');
    }

    public function destroy(Request $request, string $companyLocation): JsonResponse
    {
        $existing = $this->locationRepository->findByIdentifierOrFail($companyLocation);
        $this->authorize('deleteLocation', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->locationService->delete($companyLocation, $actor);

        return ApiResponse::success(null, 'Location deleted successfully.');
    }
}
