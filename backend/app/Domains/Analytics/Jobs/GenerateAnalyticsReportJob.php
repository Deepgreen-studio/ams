<?php

namespace App\Domains\Analytics\Jobs;

use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Analytics\Services\AnalyticsReportExportService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAnalyticsReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $reportUuid,
        public readonly string $format = 'csv',
        public readonly array $runtimeFilters = [],
        public readonly ?int $actorId = null,
        public readonly string $trigger = 'queue',
    ) {}

    public function handle(AnalyticsReportExportService $exportService): void
    {
        $report = AnalyticsReport::query()->where('uuid', $this->reportUuid)->firstOrFail();
        $actor = $this->actorId ? User::query()->find($this->actorId) : null;

        $exportService->run(
            $report,
            $this->format,
            $this->runtimeFilters,
            $actor,
            $this->trigger
        );
    }
}
