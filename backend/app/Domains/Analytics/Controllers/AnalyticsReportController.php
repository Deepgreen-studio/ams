<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Enums\AnalyticsReportFormat;
use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Jobs\GenerateAnalyticsReportJob;
use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Repositories\AnalyticsReportRunRepository;
use App\Domains\Analytics\Requests\IndexAnalyticsReportRequest;
use App\Domains\Analytics\Requests\IndexAnalyticsReportRunRequest;
use App\Domains\Analytics\Requests\PreviewAnalyticsReportRequest;
use App\Domains\Analytics\Requests\RunAnalyticsReportRequest;
use App\Domains\Analytics\Requests\ScheduleAnalyticsReportRequest;
use App\Domains\Analytics\Requests\StoreAnalyticsReportRequest;
use App\Domains\Analytics\Requests\UpdateAnalyticsReportRequest;
use App\Domains\Analytics\Resources\AnalyticsReportCollection;
use App\Domains\Analytics\Resources\AnalyticsReportResource;
use App\Domains\Analytics\Resources\AnalyticsReportRunCollection;
use App\Domains\Analytics\Resources\AnalyticsReportRunResource;
use App\Domains\Analytics\Services\AnalyticsReportDefinitionService;
use App\Domains\Analytics\Services\AnalyticsReportExportService;
use App\Domains\Analytics\Services\AnalyticsReportGeneratorService;
use App\Domains\Analytics\Services\AnalyticsReportScheduleService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsReportController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AnalyticsReportDefinitionService $reportService,
        private readonly AnalyticsReportGeneratorService $generatorService,
        private readonly AnalyticsReportExportService $exportService,
        private readonly AnalyticsReportScheduleService $scheduleService,
        private readonly AnalyticsReportRunRepository $runRepository,
    ) {}

    public function index(IndexAnalyticsReportRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $reports = $this->reportService->paginate($request->filters());

        return ApiResponse::success([
            'reports' => (new AnalyticsReportCollection($reports))->resolve(),
            'column_catalog' => $this->generatorService->availableColumns(),
            'report_types' => collect(AnalyticsReportType::cases())
                ->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()])
                ->values()
                ->all(),
            'formats' => AnalyticsReportFormat::values(),
        ]);
    }

    public function store(StoreAnalyticsReportRequest $request): JsonResponse
    {
        $this->authorize('create', AnalyticsReport::class);

        $report = $this->reportService->create($request->validated(), $request->user());

        return ApiResponse::success([
            'report' => new AnalyticsReportResource($report),
        ], 'Analytics report created.', 201);
    }

    public function show(string $report): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $model = $this->reportService->find($report);

        return ApiResponse::success([
            'report' => new AnalyticsReportResource($model),
            'column_catalog' => $this->generatorService->availableColumns(),
        ]);
    }

    public function update(UpdateAnalyticsReportRequest $request, string $report): JsonResponse
    {
        $model = $this->reportService->find($report);
        $this->authorize('update', $model);

        $updated = $this->reportService->update($model, $request->validated(), $request->user());

        return ApiResponse::success([
            'report' => new AnalyticsReportResource($updated),
        ], 'Analytics report updated.');
    }

    public function designer(UpdateAnalyticsReportRequest $request, string $report): JsonResponse
    {
        $model = $this->reportService->find($report);
        $this->authorize('update', $model);

        $updated = $this->reportService->saveDesigner($model, $request->validated(), $request->user());

        return ApiResponse::success([
            'report' => new AnalyticsReportResource($updated),
        ], 'Report designer saved.');
    }

    public function destroy(string $report): JsonResponse
    {
        $model = $this->reportService->find($report);
        $this->authorize('delete', $model);

        $this->reportService->delete($model, request()->user());

        return ApiResponse::success(null, 'Analytics report deleted.');
    }

    public function preview(PreviewAnalyticsReportRequest $request, string $report): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $model = $this->reportService->find($report);
        $dataset = $this->generatorService->generate($model, $request->validated());

        return ApiResponse::success([
            'dataset' => $dataset,
        ]);
    }

    public function run(RunAnalyticsReportRequest $request, string $report): JsonResponse
    {
        $model = $this->reportService->find($report);
        $this->authorize('export', $model);

        $validated = $request->validated();
        $format = (string) ($validated['format'] ?? $model->format_defaults['format'] ?? AnalyticsReportFormat::Csv->value);
        $async = (bool) ($validated['async'] ?? false);
        unset($validated['format'], $validated['async']);

        if ($async) {
            GenerateAnalyticsReportJob::dispatch(
                $model->uuid,
                $format,
                $validated,
                $request->user()?->id,
                'manual'
            );

            return ApiResponse::success([
                'queued' => true,
                'format' => $format,
            ], 'Report generation queued.');
        }

        $result = $this->exportService->run($model, $format, $validated, $request->user(), 'manual');

        return ApiResponse::success([
            'run' => new AnalyticsReportRunResource($result['run']),
            'dataset' => $result['dataset'],
        ], 'Report generated.');
    }

    public function runs(IndexAnalyticsReportRunRequest $request, string $report): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $model = $this->reportService->find($report);
        $runs = $this->runRepository->paginateForReport($model->id, $request->filters());

        return ApiResponse::success([
            'runs' => (new AnalyticsReportRunCollection($runs))->resolve(),
        ]);
    }

    public function downloadRun(string $report, string $run): StreamedResponse|JsonResponse
    {
        $model = $this->reportService->find($report);
        $this->authorize('export', $model);

        $runModel = $this->runRepository->findByUuidOrFail($run);

        if ((int) $runModel->analytics_report_id !== (int) $model->id) {
            return ApiResponse::error('Report run not found for this report.', 404);
        }

        return $this->exportService->download($runModel);
    }

    public function schedule(ScheduleAnalyticsReportRequest $request, string $report): JsonResponse
    {
        $model = $this->reportService->find($report);
        $this->authorize('update', $model);

        $updated = $this->scheduleService->sync($model, $request->validated(), $request->user());

        return ApiResponse::success([
            'report' => new AnalyticsReportResource($this->reportService->find($updated->uuid)),
        ], 'Report schedule updated.');
    }
}
