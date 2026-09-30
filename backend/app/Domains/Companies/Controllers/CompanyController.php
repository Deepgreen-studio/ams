<?php

namespace App\Domains\Companies\Controllers;

use App\Domains\Audit\Resources\ActivityLogResource;
use App\Domains\Audit\Resources\SystemEventResource;
use App\Domains\Companies\Enums\CompanyPermission;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Requests\StoreCompanyRequest;
use App\Domains\Companies\Requests\UpdateCompanyRequest;
use App\Domains\Companies\Requests\UploadCompanyMediaRequest;
use App\Domains\Companies\Resources\CompanyCollection;
use App\Domains\Companies\Resources\CompanyResource;
use App\Domains\Companies\Services\CompanyAccess;
use App\Domains\Companies\Services\CompanyConsoleService;
use App\Domains\Companies\Services\CompanyService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CompanyService $companyService,
        private readonly CompanyConsoleService $companyConsoleService,
        private readonly CompanyAccess $companyAccess,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->input('trashed') === 'only') {
            $this->authorize('viewTrash', Company::class);
        } else {
            $this->authorize('viewAny', Company::class);
        }

        /** @var User $actor */
        $actor = $request->user();
        $filters = $request->only([
            'search', 'status', 'country', 'sort_by', 'sort_dir', 'per_page', 'page', 'trashed',
        ]);
        $accessibleIds = $this->companyAccess->accessibleIds($actor);
        if ($accessibleIds !== null) {
            $filters['accessible_ids'] = $accessibleIds;
        }

        $companies = $this->companyService->list($filters);

        return ApiResponse::success([
            'companies' => (new CompanyCollection($companies))->resolve(),
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $this->authorize('create', Company::class);

        /** @var User $actor */
        $actor = $request->user();
        $company = $this->companyService->create($request->validated(), $actor);

        return ApiResponse::success([
            'company' => new CompanyResource($company),
        ], 'Company created successfully.', 201);
    }

    public function show(Request $request, string $company): JsonResponse
    {
        $model = $this->companyService->show($company);
        $this->companyAccess->assert($request->user(), $model);
        if ($request->user()?->can(CompanyPermission::VIEW)) {
            $this->authorize('view', $model);
        } else {
            $this->authorize('viewProfile', $model);
        }

        return ApiResponse::success([
            'company' => new CompanyResource($model),
        ]);
    }

    public function console(Request $request, string $company): JsonResponse
    {
        $model = $this->companyService->find($company);
        /** @var User $actor */
        $actor = $request->user();
        $this->companyAccess->assert($actor, $model);
        $this->authorize('viewConsole', $model);

        return ApiResponse::success([
            'console' => $this->companyConsoleService->console($model, $actor),
        ]);
    }

    public function activity(Request $request, string $company): JsonResponse
    {
        $model = $this->companyService->find($company);
        $this->companyAccess->assert($request->user(), $model);
        $this->authorize('view', $model);

        $history = $this->companyService->activityHistory($model);

        return ApiResponse::success([
            'activities' => ActivityLogResource::collection($history['activities'])->resolve(),
            'important_events' => SystemEventResource::collection($history['important_events'])->resolve(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company);
        $this->companyAccess->assert($request->user(), $existing);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->companyService->update($company, $request->validated(), $actor);

        return ApiResponse::success([
            'company' => new CompanyResource($updated),
        ], 'Company updated successfully.');
    }

    public function destroy(Request $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company);
        $this->companyAccess->assert($request->user(), $existing);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->companyService->delete($company, $actor);

        return ApiResponse::success(null, 'Company deleted successfully.');
    }

    public function restore(Request $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company, withTrashed: true);
        $this->companyAccess->assert($request->user(), $existing);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->companyService->restore($company, $actor);

        return ApiResponse::success([
            'company' => new CompanyResource($restored),
        ], 'Company restored successfully.');
    }

    public function forceDelete(Request $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company, withTrashed: true);
        $this->companyAccess->assert($request->user(), $existing);
        $this->authorize('forceDelete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->companyService->forceDelete($company, $actor);

        return ApiResponse::success(null, 'Company permanently deleted.');
    }

    public function uploadLogo(UploadCompanyMediaRequest $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company);
        $this->companyAccess->assert($request->user(), $existing);
        $this->authorize('manageBranding', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->companyService->uploadLogo($company, $request->file('file'), $actor);

        return ApiResponse::success([
            'company' => new CompanyResource($updated),
        ], 'Company logo updated successfully.');
    }

    public function uploadFavicon(UploadCompanyMediaRequest $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company);
        $this->companyAccess->assert($request->user(), $existing);
        $this->authorize('manageBranding', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->companyService->uploadFavicon($company, $request->file('file'), $actor);

        return ApiResponse::success([
            'company' => new CompanyResource($updated),
        ], 'Company favicon updated successfully.');
    }

    public function updateBranding(Request $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company);
        $this->companyAccess->assert($request->user(), $existing);
        $this->authorize('manageBranding', $existing);

        $data = $request->validate([
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'timezone'],
            'language' => ['nullable', 'string', 'max:16'],
            'currency' => ['nullable', 'string', 'size:3'],
            'date_format' => ['nullable', 'string', 'max:32'],
            'time_format' => ['nullable', 'string', 'max:32'],
            'business_hours' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->companyService->updateBranding($company, $data, $actor);

        return ApiResponse::success([
            'company' => new CompanyResource($updated),
        ], 'Company branding updated successfully.');
    }
}
