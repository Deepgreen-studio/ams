<?php

namespace App\Domains\Dashboard\Controllers;

use App\Domains\Dashboard\Services\DashboardService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $days = max(1, min(365, (int) $request->query('days', 30)));

        $data = $this->dashboardService->overview(
            actor: $request->user(),
            days: $days,
        );

        return ApiResponse::success($data);
    }
}
