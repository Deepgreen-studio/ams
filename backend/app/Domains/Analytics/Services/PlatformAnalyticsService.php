<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Repositories\PlatformAnalyticsRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformAnalyticsService
{
    public function __construct(
        private readonly PlatformAnalyticsRepository $analyticsRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->dashboard($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function notifications(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->notificationReport($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function automation(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->automationReport($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function workflows(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->workflowReport($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function ai(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->aiReport($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(array $filters = []): StreamedResponse|JsonResponse
    {
        $format = strtolower((string) ($filters['format'] ?? 'csv'));
        $report = strtolower((string) ($filters['report'] ?? 'overview'));

        if ($format === 'pdf') {
            return response()->json([
                'success' => false,
                'message' => 'PDF export is architecture-ready. Use CSV or Excel for now, or print the on-screen report.',
                'data' => [
                    'pdf_ready' => true,
                    'supported_formats' => ['csv', 'excel'],
                ],
            ], 422);
        }

        if (! in_array($format, ['csv', 'excel'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported export format. Use csv, excel, or pdf.',
            ], 422);
        }

        $scope = $this->analyticsRepository->normalizeFilters($filters);
        $payload = match ($report) {
            'notifications', 'delivery' => $this->analyticsRepository->notificationReport($scope),
            'automation' => $this->analyticsRepository->automationReport($scope),
            'workflows', 'workflow' => $this->analyticsRepository->workflowReport($scope),
            'ai' => $this->analyticsRepository->aiReport($scope),
            default => $this->analyticsRepository->dashboard($scope),
        };

        $rows = $this->analyticsRepository->exportRows($report, $payload);
        $filename = 'platform-analytics-'.$report.'-'.now()->format('Ymd-His');
        $isExcel = $format === 'excel';

        return response()->streamDownload(function () use ($rows, $isExcel): void {
            $handle = fopen('php://output', 'w');
            if ($isExcel) {
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            }
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename.($isExcel ? '.xls' : '.csv'), [
            'Content-Type' => $isExcel
                ? 'application/vnd.ms-excel; charset=UTF-8'
                : 'text/csv; charset=UTF-8',
        ]);
    }
}
