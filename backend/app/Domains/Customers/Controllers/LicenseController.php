<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexLicenseRequest;
use App\Domains\Customers\Requests\RevokeLicenseRequest;
use App\Domains\Customers\Requests\StoreLicenseRequest;
use App\Domains\Customers\Requests\UpdateLicenseRequest;
use App\Domains\Customers\Resources\LicenseCollection;
use App\Domains\Customers\Resources\LicenseResource;
use App\Domains\Customers\Services\LicenseService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly LicenseService $licenseService
    ) {}

    public function index(IndexLicenseRequest $request): JsonResponse
    {
        $this->authorize('viewLicenses', Customer::class);

        $result = $this->licenseService->list($request->filters());

        return ApiResponse::success([
            'licenses' => (new LicenseCollection($result['licenses']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function history(IndexLicenseRequest $request): JsonResponse
    {
        $this->authorize('viewLicenses', Customer::class);

        $history = $this->licenseService->history($request->filters());

        return ApiResponse::success([
            'history' => (new LicenseCollection($history))->resolve(),
        ]);
    }

    public function store(StoreLicenseRequest $request): JsonResponse
    {
        $this->authorize('manageLicenses', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $license = $this->licenseService->create($request->validated(), $actor);

        return ApiResponse::success([
            'license' => new LicenseResource($license),
        ], 'License issued successfully.', 201);
    }

    public function show(string $license): JsonResponse
    {
        $model = $this->licenseService->show($license);
        $this->authorize('viewLicense', $model);

        return ApiResponse::success([
            'license' => new LicenseResource($model),
        ]);
    }

    public function update(UpdateLicenseRequest $request, string $license): JsonResponse
    {
        $existing = $this->licenseService->find($license);
        $this->authorize('updateLicense', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->licenseService->update($license, $request->validated(), $actor);

        return ApiResponse::success([
            'license' => new LicenseResource($updated),
        ], 'License updated successfully.');
    }

    public function revoke(RevokeLicenseRequest $request, string $license): JsonResponse
    {
        $existing = $this->licenseService->find($license);
        $this->authorize('updateLicense', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $revoked = $this->licenseService->revoke($license, $actor, $request->validated('reason'));

        return ApiResponse::success([
            'license' => new LicenseResource($revoked),
        ], 'License revoked successfully.');
    }

    public function destroy(Request $request, string $license): JsonResponse
    {
        $existing = $this->licenseService->find($license);
        $this->authorize('deleteLicense', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->licenseService->delete($license, $actor);

        return ApiResponse::success(null, 'License archived successfully.');
    }

    public function restore(Request $request, string $license): JsonResponse
    {
        $existing = $this->licenseService->find($license, withTrashed: true);
        $this->authorize('restoreLicense', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->licenseService->restore($license, $actor);

        return ApiResponse::success([
            'license' => new LicenseResource($restored),
        ], 'License restored successfully.');
    }

    public function timeline(Request $request, string $license): JsonResponse
    {
        $existing = $this->licenseService->find($license);
        $this->authorize('viewLicense', $existing);

        $limit = max(1, min((int) $request->query('limit', 50), 100));

        return ApiResponse::success([
            'timeline' => $this->licenseService->timeline($license, $limit),
        ]);
    }
}
