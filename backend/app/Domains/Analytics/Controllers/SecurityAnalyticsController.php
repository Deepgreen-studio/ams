<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Requests\FilterBusinessAnalyticsRequest;
use App\Domains\Analytics\Services\SecurityAnalyticsService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecurityAnalyticsController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SecurityAnalyticsService $securityAnalyticsService,
    ) {}

    public function overview(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->securityAnalyticsService->overview(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function audit(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->securityAnalyticsService->auditDashboard(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function security(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->securityAnalyticsService->securityDashboard(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function timeline(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->securityAnalyticsService->threatTimeline(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
                (int) ($request->query('limit', 75)),
            )
        );
    }

    public function risk(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->securityAnalyticsService->riskIndicators(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function heatmap(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->securityAnalyticsService->activityHeatmapEndpoint(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function export(FilterBusinessAnalyticsRequest $request): StreamedResponse|JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();
        $format = (string) $request->query('format', 'json');

        $report = $this->securityAnalyticsService->exportReport(
            $filters['company'] ?? null,
            $filters['from'] ?? null,
            $filters['to'] ?? null,
        );

        if ($format === 'csv') {
            $filename = 'security-analytics-'.now()->format('Ymd_His').'.csv';

            return response()->streamDownload(function () use ($report): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['metric', 'value']);
                foreach ($report['overview']['kpis'] ?? [] as $key => $value) {
                    fputcsv($handle, [$key, $value]);
                }
                fputcsv($handle, []);
                fputcsv($handle, ['occurred_at', 'kind', 'severity', 'title', 'message']);
                foreach ($report['timeline'] ?? [] as $item) {
                    fputcsv($handle, [
                        $item['occurred_at'] ?? '',
                        $item['kind'] ?? '',
                        $item['severity'] ?? '',
                        $item['title'] ?? '',
                        $item['message'] ?? '',
                    ]);
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return ApiResponse::success($report, 'Security analytics export ready.');
    }

    public function capture(Request $request): JsonResponse
    {
        $this->authorize('manage', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->securityAnalyticsService->capture(
                $request->input('company'),
                $request->input('date'),
            ),
            'Security analytics snapshot captured.'
        );
    }
}
