<?php

namespace App\Domains\Integrations\Controllers;

use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Requests\StoreWebhookRequest;
use App\Domains\Integrations\Requests\TestWebhookRequest;
use App\Domains\Integrations\Requests\UpdateWebhookRequest;
use App\Domains\Integrations\Resources\WebhookCollection;
use App\Domains\Integrations\Resources\WebhookEventCollection;
use App\Domains\Integrations\Resources\WebhookEventResource;
use App\Domains\Integrations\Resources\WebhookLogCollection;
use App\Domains\Integrations\Resources\WebhookLogResource;
use App\Domains\Integrations\Resources\WebhookResource;
use App\Domains\Integrations\Services\WebhookService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WebhookService $webhookService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Webhook::class);

        $webhooks = $this->webhookService->list($request->only([
            'search', 'status', 'direction', 'company', 'company_id', 'integration_id',
            'sort_by', 'sort_dir', 'per_page', 'page', 'trashed',
        ]));

        return ApiResponse::success([
            'webhooks' => (new WebhookCollection($webhooks))->resolve(),
        ]);
    }

    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $this->authorize('create', Webhook::class);

        /** @var User $actor */
        $actor = $request->user();
        $webhook = $this->webhookService->create($request->validated(), $actor);

        return ApiResponse::success([
            'webhook' => new WebhookResource($webhook),
        ], 'Webhook created successfully.', 201);
    }

    public function show(string $webhook): JsonResponse
    {
        $model = $this->webhookService->show($webhook);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'webhook' => new WebhookResource($model),
        ]);
    }

    public function update(UpdateWebhookRequest $request, string $webhook): JsonResponse
    {
        $existing = $this->webhookService->find($webhook);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->webhookService->update($webhook, $request->validated(), $actor);

        return ApiResponse::success([
            'webhook' => new WebhookResource($updated),
        ], 'Webhook updated successfully.');
    }

    public function destroy(Request $request, string $webhook): JsonResponse
    {
        $existing = $this->webhookService->find($webhook);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->webhookService->delete($webhook, $actor);

        return ApiResponse::success(null, 'Webhook deleted successfully.');
    }

    public function test(TestWebhookRequest $request, string $webhook): JsonResponse
    {
        $existing = $this->webhookService->find($webhook);
        $this->authorize('manage', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->webhookService->testOutgoing($webhook, $request->validated(), $actor);

        return ApiResponse::success([
            'webhook' => new WebhookResource($result['webhook']),
            'log' => new WebhookLogResource($result['log']),
        ], $result['log']->status?->value === 'success'
            ? 'Webhook test delivered successfully.'
            : 'Webhook test completed with failure.');
    }

    public function logs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Webhook::class);

        $logs = $this->webhookService->listLogs($request->only([
            'search', 'status', 'direction', 'event_name', 'webhook', 'webhook_id',
            'company', 'company_id', 'is_test', 'sort_by', 'sort_dir', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'logs' => (new WebhookLogCollection($logs))->resolve(),
        ]);
    }

    public function showLog(string $log): JsonResponse
    {
        $entry = $this->webhookService->showLog($log);
        $this->authorize('viewLog', $entry);

        return ApiResponse::success([
            'log' => new WebhookLogResource($entry),
        ]);
    }

    public function retry(Request $request, string $log): JsonResponse
    {
        $entry = $this->webhookService->showLog($log);
        $this->authorize('retry', $entry);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->webhookService->retryLog($log, $actor);

        return ApiResponse::success([
            'webhook' => new WebhookResource($result['webhook']),
            'log' => new WebhookLogResource($result['log']),
        ], 'Webhook retry executed.');
    }

    public function events(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Webhook::class);

        $events = $this->webhookService->listEvents($request->only([
            'search', 'source_module', 'status', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'events' => (new WebhookEventCollection($events))->resolve(),
        ]);
    }

    public function showEvent(string $event): JsonResponse
    {
        $this->authorize('viewAny', Webhook::class);

        return ApiResponse::success([
            'event' => new WebhookEventResource($this->webhookService->showEvent($event)),
        ]);
    }

    public function incoming(Request $request, string $webhook): JsonResponse
    {
        $result = $this->webhookService->receiveIncoming($webhook, $request);
        $log = $result['log'];
        $ingest = null;
        if (is_string($log->response_body) && $log->response_body !== '') {
            $decoded = json_decode($log->response_body, true);
            if (is_array($decoded) && isset($decoded['ingest']) && is_array($decoded['ingest'])) {
                $ingest = $decoded['ingest'];
            }
        }

        return ApiResponse::success([
            'received' => true,
            'event_name' => $log->event_name,
            'log_uuid' => $log->uuid,
            'ingest' => $ingest,
            'ingest_queued' => false,
        ], 'Webhook received successfully.');
    }
}
