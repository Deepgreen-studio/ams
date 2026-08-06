<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Resources\CustomerAnalyticsSnapshotResource;
use App\Domains\Customers\Services\CustomerAnalyticsService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAnalyticsController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerAnalyticsService $analyticsService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAnalytics', Customer::class);
        $customer = (string) ($request->query('customer') ?? $request->query('customer_id') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');

        return ApiResponse::success(
            $this->analyticsService->dashboard(
                $customer,
                $request->query('from'),
                $request->query('to')
            )
        );
    }

    public function health(Request $request): JsonResponse
    {
        $this->authorize('viewAnalytics', Customer::class);
        $customer = (string) ($request->query('customer') ?? $request->query('customer_id') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');

        return ApiResponse::success($this->analyticsService->health($customer));
    }

    public function trends(Request $request): JsonResponse
    {
        $this->authorize('viewAnalytics', Customer::class);
        $customer = (string) ($request->query('customer') ?? $request->query('customer_id') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');

        return ApiResponse::success(
            $this->analyticsService->trends(
                $customer,
                $request->query('from'),
                $request->query('to')
            )
        );
    }

    public function usage(Request $request): JsonResponse
    {
        $this->authorize('viewAnalytics', Customer::class);
        $customer = (string) ($request->query('customer') ?? $request->query('customer_id') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');

        return ApiResponse::success([
            'usage' => $this->analyticsService->usage($customer),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $this->authorize('manageAnalytics', Customer::class);
        $customer = (string) ($request->input('customer') ?? $request->input('customer_id') ?? $request->query('customer') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');

        /** @var User $actor */
        $actor = $request->user();
        $snapshot = $this->analyticsService->refresh($customer, $actor);

        return ApiResponse::success([
            'snapshot' => new CustomerAnalyticsSnapshotResource($snapshot),
        ], 'Customer analytics refreshed successfully.');
    }
}
