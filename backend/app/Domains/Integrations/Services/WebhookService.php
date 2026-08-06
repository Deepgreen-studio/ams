<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Integrations\Enums\WebhookDirection;
use App\Domains\Integrations\Enums\WebhookLogStatus;
use App\Domains\Integrations\Events\WebhookCreated;
use App\Domains\Integrations\Events\WebhookDeleted;
use App\Domains\Integrations\Events\WebhookUpdated;
use App\Domains\Integrations\Jobs\DeliverOutgoingWebhookJob;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookEvent;
use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Domains\Integrations\Repositories\WebhookEventRepository;
use App\Domains\Integrations\Repositories\WebhookLogRepository;
use App\Domains\Integrations\Repositories\WebhookRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Services\Webhook\WebhookEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebhookService
{
    public function __construct(
        private readonly WebhookRepository $webhookRepository,
        private readonly WebhookLogRepository $webhookLogRepository,
        private readonly WebhookEventRepository $webhookEventRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly IntegrationRepository $integrationRepository,
        private readonly WebhookDeliveryService $deliveryService,
        private readonly WebhookEngine $webhookEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $this->normalizeCompanyFilter($filters);

        return $this->webhookRepository->paginateFiltered($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): Webhook
    {
        return $this->webhookRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): Webhook
    {
        return $this->find($identifier)->load([
            'company:id,uuid,company_name,status',
            'integration:id,uuid,name,slug,status',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Webhook
    {
        return DB::transaction(function () use ($data, $actor): Webhook {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;

            if (! empty($data['integration_id'])) {
                $integration = $this->integrationRepository->findByIdentifierOrFail((string) $data['integration_id']);
                $payload['integration_id'] = $integration->id;
            }

            $payload['slug'] = $this->resolveUniqueSlug($company->id, $payload['slug'] ?? null, $payload['name']);
            $payload['status'] = $payload['status'] ?? 'inactive';
            $payload['signature_algorithm'] = $payload['signature_algorithm'] ?? 'hmac_sha256';
            $payload['signature_header'] = $payload['signature_header'] ?? 'X-AMS-Signature';
            $payload['timeout'] = $payload['timeout'] ?? 30;
            $payload['retry_attempts'] = $payload['retry_attempts'] ?? 3;
            $payload['retry_delay_seconds'] = $payload['retry_delay_seconds'] ?? 60;
            $payload['verify_ssl'] = $payload['verify_ssl'] ?? true;
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            if (($payload['direction'] ?? null) === WebhookDirection::Outgoing->value && blank($payload['url'] ?? null)) {
                throw new ApiException('Outgoing webhooks require a destination URL.', 422);
            }

            if (empty($payload['secret'])) {
                $payload['secret'] = Str::random(48);
            }

            $webhook = $this->webhookRepository->createWebhook($payload);
            event(new WebhookCreated($webhook, $actor));

            return $webhook;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Webhook
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Webhook {
            $webhook = $this->webhookRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('integration_id', $data)) {
                if (blank($data['integration_id'])) {
                    $payload['integration_id'] = null;
                } else {
                    $integration = $this->integrationRepository->findByIdentifierOrFail((string) $data['integration_id']);
                    $payload['integration_id'] = $integration->id;
                }
            }

            if (array_key_exists('slug', $payload) || array_key_exists('name', $payload)) {
                $payload['slug'] = $this->resolveUniqueSlug(
                    $webhook->company_id,
                    $payload['slug'] ?? $webhook->slug,
                    $payload['name'] ?? $webhook->name,
                    $webhook->id
                );
            }

            if (array_key_exists('secret', $data) && blank($data['secret'])) {
                unset($payload['secret']);
            }

            if (array_key_exists('rotate_secret', $data) && $data['rotate_secret']) {
                $payload['secret'] = Str::random(48);
            }

            $updated = $this->webhookRepository->updateWebhook($webhook, $payload);
            event(new WebhookUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $webhook = $this->webhookRepository->findByIdentifierOrFail($identifier);
            $this->webhookRepository->updateWebhook($webhook, ['updated_by' => $actor->id]);
            $webhook->delete();
            event(new WebhookDeleted($webhook, $actor));
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listLogs(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['webhook']) && empty($filters['webhook_id'])) {
            $webhook = $this->webhookRepository->findByIdentifierOrFail((string) $filters['webhook']);
            $filters['webhook_id'] = $webhook->id;
        }

        $this->normalizeCompanyFilter($filters);

        return $this->webhookLogRepository->paginateFiltered($filters);
    }

    public function showLog(string $logUuid): WebhookLog
    {
        return $this->webhookLogRepository->findByUuidOrFail($logUuid)->load([
            'webhook:id,uuid,name,slug,direction,status',
            'event:id,uuid,name,label,description,source_module',
            'actor:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listEvents(array $filters = []): LengthAwarePaginator
    {
        return $this->webhookEventRepository->paginateFiltered($filters);
    }

    public function showEvent(string $uuid): WebhookEvent
    {
        return $this->webhookEventRepository->findByUuidOrFail($uuid);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchEvent(string $eventName, array $payload, ?int $companyId = null, ?User $actor = null): int
    {
        $webhooks = $this->webhookRepository->findActiveOutgoingForEvent($eventName, $companyId);
        $count = 0;

        foreach ($webhooks as $webhook) {
            $this->queueOutgoing($webhook, $eventName, $payload, $actor, isTest: false);
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{webhook: Webhook, log: WebhookLog}
     */
    public function testOutgoing(string $identifier, array $payload, User $actor): array
    {
        $webhook = $this->webhookRepository->findByIdentifierOrFail($identifier);
        if ($webhook->direction !== WebhookDirection::Outgoing) {
            throw new ApiException('Only outgoing webhooks can be tested with delivery.', 422);
        }

        $eventName = (string) ($payload['event_name'] ?? 'webhook.test');
        $body = (array) ($payload['payload'] ?? [
            'message' => 'AMS webhook test payload',
            'tested_at' => now()->toIso8601String(),
        ]);

        $log = $this->queueOutgoing($webhook, $eventName, $body, $actor, isTest: true, sync: true);

        return [
            'webhook' => $webhook->refresh(),
            'log' => $log->fresh(['webhook', 'event', 'actor']) ?? $log,
        ];
    }

    /**
     * @return array{webhook: Webhook, log: WebhookLog}
     */
    public function retryLog(string $logUuid, User $actor): array
    {
        $log = $this->webhookLogRepository->findByUuidOrFail($logUuid);
        if (! in_array($log->status, [WebhookLogStatus::Failed, WebhookLogStatus::Retrying], true)) {
            throw new ApiException('Only failed or retrying webhook logs can be retried.', 422);
        }

        $webhook = $log->webhook;
        if (! $webhook || $webhook->direction !== WebhookDirection::Outgoing) {
            throw new ApiException('Retry is only supported for outgoing webhook deliveries.', 422);
        }

        $payload = [];
        if (filled($log->request_body)) {
            $decoded = json_decode((string) $log->request_body, true);
            $payload = is_array($decoded) ? $decoded : ['raw' => $log->request_body];
            if (isset($payload['data']) && is_array($payload['data'])) {
                $payload = $payload['data'];
            }
        }

        $newLog = $this->queueOutgoing(
            $webhook,
            (string) ($log->event_name ?: 'webhook.retry'),
            $payload,
            $actor,
            isTest: (bool) $log->is_test,
            sync: true,
        );

        return [
            'webhook' => $webhook->refresh(),
            'log' => $newLog,
        ];
    }

    /**
     * @return array{webhook: Webhook, log: WebhookLog, payload: array<string, mixed>}
     */
    public function receiveIncoming(string $identifier, Request $request): array
    {
        $webhook = $this->webhookRepository->findByIdentifierOrFail($identifier);
        $received = $this->webhookEngine->receive($webhook->toEngineConfig(), $request);

        $eventName = (string) ($received['payload']['event'] ?? $received['payload']['event_name'] ?? 'incoming.webhook');
        $event = $this->webhookEventRepository->findByName($eventName);

        $log = $this->webhookLogRepository->createLog([
            'webhook_id' => $webhook->id,
            'company_id' => $webhook->company_id,
            'webhook_event_id' => $event?->id,
            'direction' => 'incoming',
            'event_name' => $eventName,
            'status' => WebhookLogStatus::Success->value,
            'method' => strtoupper($request->method()),
            'url' => $request->fullUrl(),
            'request_headers' => $this->maskIncomingHeaders($received['headers']),
            'request_body' => $this->truncate((string) $received['raw_body']),
            'response_status' => 200,
            'response_body' => json_encode(['received' => true]),
            'duration_ms' => 0,
            'attempts' => 1,
            'max_attempts' => 1,
            'is_test' => (bool) ($received['payload']['is_test'] ?? false),
        ]);

        $this->webhookRepository->updateWebhook($webhook, [
            'last_triggered_at' => now(),
            'last_success_at' => now(),
        ]);

        return [
            'webhook' => $webhook->refresh(),
            'log' => $log,
            'payload' => $received['payload'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function queueOutgoing(
        Webhook $webhook,
        string $eventName,
        array $payload,
        ?User $actor,
        bool $isTest = false,
        bool $sync = false,
    ): WebhookLog {
        $event = $this->webhookEventRepository->findByName($eventName);
        $envelope = [
            'event' => $eventName,
            'webhook_uuid' => $webhook->uuid,
            'is_test' => $isTest,
            'sent_at' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $log = $this->webhookLogRepository->createLog([
            'webhook_id' => $webhook->id,
            'company_id' => $webhook->company_id,
            'webhook_event_id' => $event?->id,
            'direction' => 'outgoing',
            'event_name' => $eventName,
            'status' => WebhookLogStatus::Pending->value,
            'method' => 'POST',
            'url' => $webhook->url,
            'request_body' => json_encode($envelope),
            'attempts' => 0,
            'max_attempts' => max(1, (int) $webhook->retry_attempts),
            'is_test' => $isTest,
            'triggered_by' => $actor?->id,
        ]);

        $this->webhookRepository->updateWebhook($webhook, ['last_triggered_at' => now()]);

        if ($sync || app()->environment('testing')) {
            $this->deliveryService->processQueuedDelivery($log->id);

            return $this->webhookLogRepository->findByUuidOrFail($log->uuid);
        }

        DeliverOutgoingWebhookJob::dispatch($log->id, $webhook->company_id, $actor?->id);

        return $log;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'name', 'slug', 'description', 'direction', 'status', 'url', 'secret',
            'signature_algorithm', 'signature_header', 'subscribed_events', 'headers',
            'timeout', 'retry_attempts', 'retry_delay_seconds', 'verify_ssl',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['description', 'url', 'slug'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        return $payload;
    }

    protected function resolveUniqueSlug(int $companyId, ?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'webhook';
        $candidate = $base;
        $suffix = 2;
        while ($this->webhookRepository->slugExistsForCompany($companyId, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function normalizeCompanyFilter(array &$filters): void
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier);
            $filters['company_id'] = $company->id;
        }
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function maskIncomingHeaders(array $headers): array
    {
        $masked = [];
        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            if (str_contains($lower, 'signature') || str_contains($lower, 'authorization') || str_contains($lower, 'secret')) {
                $masked[$key] = '***MASKED***';
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    protected function truncate(?string $value, int $limit = 50000): ?string
    {
        if ($value === null) {
            return null;
        }

        return strlen($value) <= $limit ? $value : substr($value, 0, $limit).'...[truncated]';
    }
}
