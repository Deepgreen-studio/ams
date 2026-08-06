<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsReportFormat;
use App\Domains\Analytics\Enums\AnalyticsReportRunStatus;
use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Analytics\Models\AnalyticsReportRun;
use App\Domains\Analytics\Repositories\AnalyticsReportRunRepository;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AnalyticsReportExportService
{
    public function __construct(
        private readonly AnalyticsReportGeneratorService $generatorService,
        private readonly AnalyticsReportRunRepository $runRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $runtimeFilters
     * @return array{run: AnalyticsReportRun, dataset: array<string, mixed>}
     */
    public function run(
        AnalyticsReport $report,
        string $format,
        array $runtimeFilters = [],
        ?User $actor = null,
        string $trigger = 'manual'
    ): array {
        /** @var AnalyticsReportRun $run */
        $run = $this->runRepository->create([
            'analytics_report_id' => $report->id,
            'status' => AnalyticsReportRunStatus::Running->value,
            'format' => $format,
            'trigger' => $trigger,
            'filters_snapshot' => $runtimeFilters,
            'started_at' => now(),
            'created_by' => $actor?->id,
        ]);

        try {
            $dataset = $this->generatorService->generate($report, $runtimeFilters);
            $artifact = $this->persistArtifact($report, $dataset, $format);

            $run->update([
                'status' => AnalyticsReportRunStatus::Completed->value,
                'row_count' => (int) ($dataset['meta']['row_count'] ?? 0),
                'file_path' => $artifact['path'],
                'file_name' => $artifact['name'],
                'mime_type' => $artifact['mime'],
                'file_size' => $artifact['size'],
                'result_meta' => [
                    'report_type' => $dataset['meta']['report_type'] ?? null,
                    'has_groups' => ! empty($dataset['groups']),
                    'has_chart' => ! empty($dataset['chart']),
                ],
                'completed_at' => now(),
            ]);

            $report->update(['last_run_at' => now()]);

            return [
                'run' => $run->fresh(['creator']),
                'dataset' => $dataset,
            ];
        } catch (Throwable $exception) {
            $run->update([
                'status' => AnalyticsReportRunStatus::Failed->value,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array{path: string, name: string, mime: string, size: int}
     */
    protected function persistArtifact(AnalyticsReport $report, array $dataset, string $format): array
    {
        $slug = Str::slug($report->name) ?: 'report';
        $stamp = now()->format('Ymd_His');
        $disk = Storage::disk(config('filesystems.analytics_reports_disk', 'local'));

        return match ($format) {
            AnalyticsReportFormat::Csv->value => $this->writeCsv($disk, $dataset, $slug, $stamp),
            AnalyticsReportFormat::Excel->value => $this->writeExcel($disk, $dataset, $slug, $stamp),
            AnalyticsReportFormat::Pdf->value, AnalyticsReportFormat::Print->value => $this->writePdf($disk, $report, $dataset, $slug, $stamp, $format),
            AnalyticsReportFormat::Json->value => $this->writeJson($disk, $dataset, $slug, $stamp),
            default => $this->writeCsv($disk, $dataset, $slug, $stamp),
        };
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array{path: string, name: string, mime: string, size: int}
     */
    protected function writeCsv($disk, array $dataset, string $slug, string $stamp): array
    {
        $name = "{$slug}_{$stamp}.csv";
        $path = "analytics/reports/{$name}";
        $handle = fopen('php://temp', 'r+');
        $headers = array_column($dataset['columns'], 'label');
        fputcsv($handle, $headers);

        foreach ($dataset['rows'] as $row) {
            $line = [];
            foreach ($dataset['columns'] as $column) {
                $line[] = $row[$column['key']] ?? '';
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        $disk->put($path, $contents);

        return [
            'path' => $path,
            'name' => $name,
            'mime' => 'text/csv',
            'size' => strlen($contents),
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array{path: string, name: string, mime: string, size: int}
     */
    protected function writeExcel($disk, array $dataset, string $slug, string $stamp): array
    {
        $name = "{$slug}_{$stamp}.xlsx";
        $path = "analytics/reports/{$name}";

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');

        $colIndex = 1;
        foreach ($dataset['columns'] as $column) {
            $sheet->setCellValue([$colIndex, 1], $column['label']);
            $colIndex++;
        }

        $rowIndex = 2;
        foreach ($dataset['rows'] as $row) {
            $colIndex = 1;
            foreach ($dataset['columns'] as $column) {
                $sheet->setCellValue([$colIndex, $rowIndex], $row[$column['key']] ?? '');
                $colIndex++;
            }
            $rowIndex++;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ams_xlsx_');
        (new Xlsx($spreadsheet))->save($tmp);
        $contents = file_get_contents($tmp) ?: '';
        @unlink($tmp);

        $disk->put($path, $contents);

        return [
            'path' => $path,
            'name' => $name,
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size' => strlen($contents),
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array{path: string, name: string, mime: string, size: int}
     */
    protected function writePdf($disk, AnalyticsReport $report, array $dataset, string $slug, string $stamp, string $format): array
    {
        $name = "{$slug}_{$stamp}.pdf";
        $path = "analytics/reports/{$name}";

        $html = view('analytics.report-pdf', [
            'report' => $report,
            'dataset' => $dataset,
            'printMode' => $format === AnalyticsReportFormat::Print->value,
        ])->render();

        $contents = Pdf::loadHTML($html)->setPaper('a4', 'landscape')->output();
        $disk->put($path, $contents);

        return [
            'path' => $path,
            'name' => $name,
            'mime' => 'application/pdf',
            'size' => strlen($contents),
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @return array{path: string, name: string, mime: string, size: int}
     */
    protected function writeJson($disk, array $dataset, string $slug, string $stamp): array
    {
        $name = "{$slug}_{$stamp}.json";
        $path = "analytics/reports/{$name}";
        $contents = json_encode($dataset, JSON_PRETTY_PRINT) ?: '{}';
        $disk->put($path, $contents);

        return [
            'path' => $path,
            'name' => $name,
            'mime' => 'application/json',
            'size' => strlen($contents),
        ];
    }

    public function download(AnalyticsReportRun $run): StreamedResponse
    {
        if ($run->status !== AnalyticsReportRunStatus::Completed || blank($run->file_path)) {
            abort(404, 'Report artifact not available.');
        }

        $disk = Storage::disk($run->disk());

        return response()->streamDownload(function () use ($disk, $run): void {
            echo $disk->get($run->file_path);
        }, $run->file_name ?: 'report.bin', [
            'Content-Type' => $run->mime_type ?: 'application/octet-stream',
        ]);
    }
}
