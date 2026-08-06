<?php

namespace App\Domains\Compliance\Controllers;

use App\Domains\Compliance\Models\ComplianceCase;
use App\Domains\Compliance\Requests\ExportComplianceAnalyticsRequest;
use App\Domains\Compliance\Requests\FilterComplianceAnalyticsRequest;
use App\Domains\Compliance\Services\ComplianceAnalyticsService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceAnalyticsController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ComplianceAnalyticsService $complianceAnalyticsService
    ) {}

    public function dashboard(FilterComplianceAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceCase::class);

        return ApiResponse::success(
            $this->complianceAnalyticsService->dashboard($request->validated())
        );
    }

    public function risks(FilterComplianceAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceCase::class);

        return ApiResponse::success(
            $this->complianceAnalyticsService->riskCharts($request->validated())
        );
    }

    public function gdprReport(FilterComplianceAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceCase::class);

        return ApiResponse::success(
            $this->complianceAnalyticsService->gdprReport($request->validated())
        );
    }

    public function consentReport(FilterComplianceAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceCase::class);

        return ApiResponse::success(
            $this->complianceAnalyticsService->consentReport($request->validated())
        );
    }

    public function auditReport(FilterComplianceAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceCase::class);

        return ApiResponse::success(
            $this->complianceAnalyticsService->auditReport($request->validated())
        );
    }

    public function export(ExportComplianceAnalyticsRequest $request): StreamedResponse|JsonResponse
    {
        $this->authorize('viewAny', ComplianceCase::class);

        return $this->complianceAnalyticsService->export($request->validated());
    }
}
