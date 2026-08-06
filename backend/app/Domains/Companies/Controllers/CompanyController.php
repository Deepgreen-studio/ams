<?php

namespace App\Domains\Companies\Controllers;

use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Requests\StoreCompanyRequest;
use App\Domains\Companies\Requests\UpdateCompanyRequest;
use App\Domains\Companies\Requests\UploadCompanyMediaRequest;
use App\Domains\Companies\Resources\CompanyCollection;
use App\Domains\Companies\Resources\CompanyResource;
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
        private readonly CompanyService $companyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Company::class);

        $companies = $this->companyService->list($request->only([
            'search', 'status', 'country', 'sort_by', 'sort_dir', 'per_page', 'page', 'trashed',
        ]));

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

    public function show(string $company): JsonResponse
    {
        $model = $this->companyService->show($company);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'company' => new CompanyResource($model),
        ]);
    }

    public function update(UpdateCompanyRequest $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company);
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
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->companyService->delete($company, $actor);

        return ApiResponse::success(null, 'Company deleted successfully.');
    }

    public function restore(Request $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->companyService->restore($company, $actor);

        return ApiResponse::success([
            'company' => new CompanyResource($restored),
        ], 'Company restored successfully.');
    }

    public function uploadLogo(UploadCompanyMediaRequest $request, string $company): JsonResponse
    {
        $existing = $this->companyService->find($company);
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
