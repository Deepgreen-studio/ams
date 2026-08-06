<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Resources\CustomerTaskResource;
use App\Domains\Customers\Services\CustomerCommunicationCenterService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerCommunicationCenterController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerCommunicationCenterService $centerService
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $this->authorize('viewCommunications', Customer::class);
        $customer = (string) ($request->query('customer') ?? $request->query('customer_id') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');

        $overview = $this->centerService->overview($customer);
        $overview['reminders'] = CustomerTaskResource::collection($overview['reminders'])->resolve();

        return ApiResponse::success($overview);
    }

    public function timeline(Request $request): JsonResponse
    {
        $this->authorize('viewCommunications', Customer::class);
        $customer = (string) ($request->query('customer') ?? $request->query('customer_id') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');
        $limit = max(1, min((int) $request->query('limit', 50), 100));

        return ApiResponse::success([
            'timeline' => $this->centerService->communicationTimeline($customer, $limit),
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $this->authorize('viewCommunications', Customer::class);
        $customer = (string) ($request->query('customer') ?? $request->query('customer_id') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');
        $limit = max(1, min((int) $request->query('limit', 50), 100));

        return ApiResponse::success([
            'activity' => $this->centerService->activityTimeline($customer, $limit),
        ]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $this->authorize('viewCommunications', Customer::class);
        $customer = (string) ($request->query('customer') ?? $request->query('customer_id') ?? '');
        abort_if($customer === '', 422, 'Customer is required.');

        return ApiResponse::success([
            'reminders' => CustomerTaskResource::collection(
                $this->centerService->reminderCalendar(
                    $customer,
                    $request->query('from'),
                    $request->query('to')
                )
            )->resolve(),
        ]);
    }
}
