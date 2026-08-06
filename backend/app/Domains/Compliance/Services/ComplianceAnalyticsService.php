<?php

namespace App\Domains\Compliance\Services;

use App\Domains\Compliance\Repositories\ComplianceAnalyticsRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceAnalyticsService
{
    public function __construct(
        private readonly ComplianceAnalyticsRepository $analyticsRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->overview($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function riskCharts(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->riskCharts($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function gdprReport(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->gdprReport($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function consentReport(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->consentReport($scope);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function auditReport(array $filters = []): array
    {
        $scope = $this->analyticsRepository->normalizeFilters($filters);

        return $this->analyticsRepository->auditReport($scope);
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
            'gdpr' => $this->analyticsRepository->gdprReport($scope),
            'consent' => $this->analyticsRepository->consentReport($scope),
            'audit' => $this->analyticsRepository->auditReport($scope),
            'risks' => $this->analyticsRepository->riskCharts($scope),
            default => $this->analyticsRepository->overview($scope),
        };

        $rows = $report === 'overview' || $report === 'dashboard'
            ? $this->analyticsRepository->exportRows($payload)
            : $this->flattenReportRows($report, $payload);

        $filename = 'compliance-'.$report.'-'.now()->format('Ymd-His');
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

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<int, string|int|float>>
     */
    private function flattenReportRows(string $report, array $payload): array
    {
        $rows = [
            ['Report', $report],
            ['Period From', $payload['period']['from'] ?? ''],
            ['Period To', $payload['period']['to'] ?? ''],
            ['Section', 'Key', 'Value'],
        ];

        $walk = function (string $section, mixed $value) use (&$rows, &$walk): void {
            if (is_array($value)) {
                $isList = array_is_list($value) && (empty($value) || ! is_array($value[0] ?? null));
                if ($isList) {
                    $rows[] = [$section, 'series', implode('|', array_map('strval', $value))];

                    return;
                }
                foreach ($value as $key => $child) {
                    if (is_scalar($child) || $child === null) {
                        $rows[] = [$section, (string) $key, $child ?? ''];
                    } elseif (is_array($child) && ! array_is_list($child)) {
                        $walk($section.'.'.$key, $child);
                    }
                }
            }
        };

        foreach ($payload as $key => $value) {
            if ($key === 'period' || $key === 'trends' || $key === 'recent' || $key === 'top_risks') {
                continue;
            }
            $walk((string) $key, $value);
        }

        return $rows;
    }
}
