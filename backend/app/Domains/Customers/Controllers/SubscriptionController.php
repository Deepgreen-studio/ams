<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexSubscriptionRequest;
use App\Domains\Customers\Requests\StoreSubscriptionRequest;
use App\Domains\Customers\Requests\UpdateSubscriptionRequest;
use App\Domains\Customers\Resources\SubscriptionCollection;
use App\Domains\Customers\Resources\SubscriptionResource;
use App\Domains\Customers\Services\SubscriptionService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function dashboard(IndexSubscriptionRequest $request): JsonResponse
    {
        $this->authorize('viewSubscriptions', Customer::class);

        $result = $this->subscriptionService->dashboard($request->filters());

        return ApiResponse::success([
            'subscriptions' => (new SubscriptionCollection($result['subscriptions']))->resolve(),
            'statistics' => $result['statistics'],
            'renewal_reminders' => SubscriptionResource::collection($result['renewal_reminders'])->resolve(),
        ]);
    }

    public function index(IndexSubscriptionRequest $request): JsonResponse
    {
        $this->authorize('viewSubscriptions', Customer::class);

        $subscriptions = $this->subscriptionService->list($request->filters());

        return ApiResponse::success([
            'subscriptions' => (new SubscriptionCollection($subscriptions))->resolve(),
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewSubscriptions', Customer::class);

        $customer = $request->query('customer') ?? $request->query('customer_id');

        return ApiResponse::success([
            'statistics' => $this->subscriptionService->statistics(
                is_string($customer) || is_numeric($customer) ? (string) $customer : null
            ),
        ]);
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $this->authorize('manageSubscriptions', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $issueLicense = (bool) ($validated['issue_license'] ?? true);
        unset($validated['issue_license']);

        $subscription = $this->subscriptionService->create($validated, $actor, $issueLicense);

        return ApiResponse::success([
            'subscription' => new SubscriptionResource($subscription),
        ], 'Subscription created successfully.', 201);
    }

    public function show(string $subscription): JsonResponse
    {
        $model = $this->subscriptionService->show($subscription);
        $this->authorize('viewSubscription', $model);

        return ApiResponse::success([
            'subscription' => new SubscriptionResource($model),
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, string $subscription): JsonResponse
    {
        $existing = $this->subscriptionService->find($subscription);
        $this->authorize('updateSubscription', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->subscriptionService->update($subscription, $request->validated(), $actor);

        return ApiResponse::success([
            'subscription' => new SubscriptionResource($updated),
        ], 'Subscription updated successfully.');
    }

    public function cancel(Request $request, string $subscription): JsonResponse
    {
        $existing = $this->subscriptionService->find($subscription);
        $this->authorize('updateSubscription', $existing);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $cancelled = $this->subscriptionService->cancel($subscription, $actor, $data['reason'] ?? null);

        return ApiResponse::success([
            'subscription' => new SubscriptionResource($cancelled),
        ], 'Subscription cancelled successfully.');
    }

    public function destroy(Request $request, string $subscription): JsonResponse
    {
        $existing = $this->subscriptionService->find($subscription);
        $this->authorize('deleteSubscription', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->subscriptionService->delete($subscription, $actor);

        return ApiResponse::success(null, 'Subscription archived successfully.');
    }

    public function restore(Request $request, string $subscription): JsonResponse
    {
        $existing = $this->subscriptionService->find($subscription, withTrashed: true);
        $this->authorize('restoreSubscription', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->subscriptionService->restore($subscription, $actor);

        return ApiResponse::success([
            'subscription' => new SubscriptionResource($restored),
        ], 'Subscription restored successfully.');
    }

    public function timeline(Request $request, string $subscription): JsonResponse
    {
        $existing = $this->subscriptionService->find($subscription);
        $this->authorize('viewSubscription', $existing);

        $limit = max(1, min((int) $request->query('limit', 50), 100));

        return ApiResponse::success([
            'timeline' => $this->subscriptionService->timeline($subscription, $limit),
        ]);
    }
}
