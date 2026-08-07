<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Contracts\IncomingWebhookHandlerInterface;
use App\Domains\Integrations\Handlers\EasyCareIncomingWebhookHandler;
use App\Domains\Integrations\Handlers\GenericSupportIncomingWebhookHandler;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Integrations\Repositories\WebhookLogRepository;
use App\Domains\Integrations\Repositories\WebhookRepository;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class IncomingWebhookIngestService
{
    public function __construct(
        private readonly WebhookRepository $webhookRepository,
        private readonly WebhookLogRepository $webhookLogRepository,
        private readonly EasyCareIncomingWebhookHandler $easyCareHandler,
        private readonly GenericSupportIncomingWebhookHandler $genericSupportHandler,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function processLog(int $webhookLogId): array
    {
        /** @var WebhookLog $log */
        $log = $this->webhookLogRepository->findOrFail($webhookLogId);
        $webhook = $log->webhook ?? $this->webhookRepository->findOrFail((int) $log->webhook_id);
        $webhook->loadMissing(['company']);

        $existing = $this->decodeIngestResult($log->response_body);
        if (is_array($existing) && ($existing['ingest']['completed'] ?? false) === true) {
            return $existing['ingest'];
        }

        $payload = $this->decodePayload($log->request_body);
        $actor = $this->resolveSystemActor($webhook);

        if ($actor === null) {
            $result = [
                'completed' => true,
                'handled' => false,
                'skipped' => true,
                'reason' => 'No system actor available to create Support/Compliance records.',
                'actions' => [],
            ];
            $this->persistIngestResult($log, $result);

            return $result;
        }

        try {
            $handlerResult = $this->runHandlers($webhook, $log, $payload, $actor);
            $result = array_merge($handlerResult, [
                'completed' => true,
                'actor_id' => $actor->id,
                'webhook_slug' => $webhook->slug,
                'event_name' => $log->event_name,
            ]);
            $this->persistIngestResult($log, $result);

            return $result;
        } catch (Throwable $e) {
            Log::error('Incoming webhook auto-ingest failed.', [
                'webhook_log_id' => $log->id,
                'webhook_slug' => $webhook->slug,
                'event_name' => $log->event_name,
                'error' => $e->getMessage(),
            ]);

            $result = [
                'completed' => false,
                'handled' => false,
                'skipped' => false,
                'reason' => $e->getMessage(),
                'actions' => ['ingest_failed'],
            ];
            $this->persistIngestResult($log, $result, failed: true);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function runHandlers(Webhook $webhook, WebhookLog $log, array $payload, User $actor): array
    {
        $lastSkip = [
            'handled' => false,
            'skipped' => true,
            'reason' => 'No auto-ingest handler matched this webhook/event.',
            'actions' => [],
        ];

        foreach ($this->handlers() as $handler) {
            if (! $handler->supports($webhook)) {
                continue;
            }

            $result = $handler->handle($webhook, $log, $payload, $actor);

            // Handler fully processed (created or idempotent hit).
            if (($result['handled'] ?? false) === true) {
                return $result;
            }

            // Unmapped event for this handler — try the next one.
            if (($result['skipped'] ?? false) === true) {
                $lastSkip = $result;

                continue;
            }

            return $result;
        }

        return $lastSkip;
    }

    /**
     * App-specific handlers first, then generic Support SMS/message ingest.
     *
     * @return list<IncomingWebhookHandlerInterface>
     */
    private function handlers(): array
    {
        return [
            $this->easyCareHandler,
            $this->genericSupportHandler,
        ];
    }

    private function resolveSystemActor(Webhook $webhook): ?User
    {
        $byEmail = User::query()->where('email', 'admin@ams.test')->first();
        if ($byEmail !== null) {
            return $byEmail;
        }

        if ($webhook->created_by) {
            $creator = User::query()->find($webhook->created_by);
            if ($creator !== null) {
                return $creator;
            }
        }

        if ($webhook->company_id) {
            $company = Company::query()->with(['users' => fn ($q) => $q->orderBy('users.id')->limit(1)])->find($webhook->company_id);
            $companyUser = $company?->users->first();
            if ($companyUser !== null) {
                return $companyUser;
            }
        }

        return User::query()->orderBy('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(?string $body): array
    {
        if ($body === null || $body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeIngestResult(?string $responseBody): ?array
    {
        if ($responseBody === null || $responseBody === '') {
            return null;
        }

        $decoded = json_decode($responseBody, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function persistIngestResult(WebhookLog $log, array $result, bool $failed = false): void
    {
        $existing = $this->decodeIngestResult($log->response_body) ?? ['received' => true];
        $existing['ingest'] = $result;
        $existing['ingest_at'] = now()->toIso8601String();

        $this->webhookLogRepository->updateLog($log, [
            'response_body' => json_encode($existing),
            'error_message' => $failed ? (string) ($result['reason'] ?? 'ingest_failed') : $log->error_message,
        ]);
    }
}
