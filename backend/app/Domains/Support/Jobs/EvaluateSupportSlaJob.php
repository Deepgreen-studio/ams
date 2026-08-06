<?php

namespace App\Domains\Support\Jobs;

use App\Domains\Support\Services\SupportSlaTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateSupportSlaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $limit = 200
    ) {}

    public function handle(SupportSlaTrackingService $trackingService): void
    {
        $trackingService->evaluateOpenTickets(null, $this->limit);
    }
}
