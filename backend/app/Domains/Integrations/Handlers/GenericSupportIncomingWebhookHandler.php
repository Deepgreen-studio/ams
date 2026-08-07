<?php

namespace App\Domains\Integrations\Handlers;

use App\Domains\Applications\Models\Application;
use App\Domains\Integrations\Contracts\IncomingWebhookHandlerInterface;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Services\SupportTicketService;
use App\Models\User;

/**
 * Generic ingest for ANY connected app/website.
 *
 * External apps POST signed payloads to their AMS incoming webhook URL using
 * the standard Support events below. No app-specific slug is required.
 */
class GenericSupportIncomingWebhookHandler implements IncomingWebhookHandlerInterface
{
    /**
     * @var list<string>
     */
    public const HANDLED_EVENTS = [
        'support.sms.received',
        'support.message.received',
        'support.ticket.created',
    ];

    public function __construct(
        private readonly SupportTicketService $supportTicketService,
    ) {}

    public function supports(Webhook $webhook): bool
    {
        return $webhook->direction?->value === 'incoming'
            || (string) $webhook->direction === 'incoming';
    }

    public function handle(Webhook $webhook, WebhookLog $log, array $payload, User $actor): array
    {
        $eventName = (string) ($payload['event'] ?? $payload['event_name'] ?? $log->event_name ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if (! in_array($eventName, self::HANDLED_EVENTS, true)) {
            return [
                'handled' => false,
                'skipped' => true,
                'reason' => 'Event not in generic Support ingest catalog: '.$eventName,
                'actions' => [],
            ];
        }

        $body = trim((string) ($data['body'] ?? $data['message'] ?? $data['description'] ?? ''));
        if ($body === '') {
            return [
                'handled' => false,
                'skipped' => true,
                'reason' => 'Support ingest requires data.body (or message/description).',
                'actions' => [],
            ];
        }

        $externalId = $this->resolveExternalId($data, $log);
        $idempotencyTag = $this->idempotencyTag($webhook, $eventName, $externalId);

        $existing = $this->findExistingTicket((int) $webhook->company_id, $idempotencyTag);
        if ($existing !== null) {
            return [
                'handled' => true,
                'skipped' => true,
                'reason' => 'Idempotent skip — Support ticket already exists for this message.',
                'support_ticket_uuid' => $existing->uuid,
                'support_ticket_number' => $existing->ticket_number,
                'privacy_request_uuid' => $existing->privacyRequest?->uuid,
                'privacy_request_number' => $existing->privacyRequest?->request_number,
                'actions' => ['idempotent_skip'],
            ];
        }

        $source = $this->resolveSource($eventName, $data);
        $involvesPersonalData = filter_var($data['involves_personal_data'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $application = $this->resolveApplication($webhook, $data);

        $from = (string) ($data['from'] ?? $data['sender'] ?? $data['phone'] ?? '');
        $subject = trim((string) ($data['subject'] ?? ''));
        if ($subject === '') {
            $subject = $source === SupportTicketSource::Sms
                ? 'SMS support'.($from !== '' ? ' from '.$from : '')
                : 'Support message from '.($webhook->name ?: ($webhook->slug ?? 'connected app'));
        }

        $ticket = $this->supportTicketService->create([
            'company_id' => $webhook->company?->uuid ?? (string) $webhook->company_id,
            'application_id' => $application?->uuid,
            'subject' => mb_substr($subject, 0, 255),
            'description' => $this->buildDescription($eventName, $data, $payload, $idempotencyTag, $log, $webhook, $body),
            'category' => $this->resolveCategory($data)->value,
            'priority' => $this->resolvePriority($data)->value,
            'source' => $source->value,
            'involves_personal_data' => $involvesPersonalData,
        ], $actor);

        $ticket->loadMissing(['privacyRequest']);

        $actions = ['support_ticket_created'];
        if ($ticket->privacy_request_id !== null) {
            $actions[] = 'compliance_privacy_request_created';
        }

        return [
            'handled' => true,
            'skipped' => false,
            'support_ticket_uuid' => $ticket->uuid,
            'support_ticket_number' => $ticket->ticket_number,
            'privacy_request_uuid' => $ticket->privacyRequest?->uuid,
            'privacy_request_number' => $ticket->privacyRequest?->request_number,
            'actions' => $actions,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveExternalId(array $data, WebhookLog $log): string
    {
        foreach (['message_id', 'sms_id', 'external_id', 'uuid', 'id'] as $key) {
            if (! empty($data[$key])) {
                return (string) $data[$key];
            }
        }

        return 'log-'.$log->uuid;
    }

    private function idempotencyTag(Webhook $webhook, string $eventName, string $externalId): string
    {
        $slug = $webhook->slug ?: 'webhook-'.$webhook->id;

        return '[ams-support-ingest:'.$slug.':'.$eventName.':'.$externalId.']';
    }

    private function findExistingTicket(int $companyId, string $idempotencyTag): ?SupportTicket
    {
        return SupportTicket::query()
            ->where('company_id', $companyId)
            ->where('description', 'like', '%'.$idempotencyTag.'%')
            ->with(['privacyRequest'])
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveApplication(Webhook $webhook, array $data): ?Application
    {
        $companyId = (int) $webhook->company_id;

        if (! empty($data['application_uuid'])) {
            return Application::query()
                ->where('company_id', $companyId)
                ->where('uuid', (string) $data['application_uuid'])
                ->first();
        }

        if (! empty($data['application_slug'])) {
            return Application::query()
                ->where('company_id', $companyId)
                ->where('slug', (string) $data['application_slug'])
                ->first();
        }

        if ($webhook->integration_id) {
            return Application::query()
                ->where('company_id', $companyId)
                ->where('integration_id', $webhook->integration_id)
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSource(string $eventName, array $data): SupportTicketSource
    {
        $channel = strtolower((string) ($data['channel'] ?? ''));

        if ($eventName === 'support.sms.received' || $channel === 'sms') {
            return SupportTicketSource::Sms;
        }

        return match ($channel) {
            'email' => SupportTicketSource::Email,
            'phone' => SupportTicketSource::Phone,
            'chat' => SupportTicketSource::Chat,
            'web' => SupportTicketSource::Web,
            'portal' => SupportTicketSource::Portal,
            default => SupportTicketSource::Api,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCategory(array $data): SupportTicketCategory
    {
        $category = SupportTicketCategory::tryFrom((string) ($data['category'] ?? ''));

        return $category ?? SupportTicketCategory::CustomerSupport;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePriority(array $data): SupportTicketPriority
    {
        $priority = SupportTicketPriority::tryFrom((string) ($data['priority'] ?? ''));

        return $priority ?? SupportTicketPriority::Medium;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     */
    private function buildDescription(
        string $eventName,
        array $data,
        array $payload,
        string $idempotencyTag,
        WebhookLog $log,
        Webhook $webhook,
        string $body,
    ): string {
        $lines = [
            $body,
            '',
            '---',
            'Auto-ingested from connected app webhook.',
            'App / webhook: '.($webhook->name ?: ($webhook->slug ?? 'n/a')),
            'Event: '.$eventName,
            'Webhook log: '.$log->uuid,
            'Received at: '.($payload['timestamp'] ?? now()->toIso8601String()),
        ];

        foreach ([
            'from' => $data['from'] ?? $data['sender'] ?? null,
            'to' => $data['to'] ?? $data['recipient'] ?? null,
            'customer_name' => $data['customer_name'] ?? $data['name'] ?? null,
            'customer_email' => $data['customer_email'] ?? $data['email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? $data['phone'] ?? null,
            'message_id' => $data['message_id'] ?? $data['sms_id'] ?? $data['external_id'] ?? null,
        ] as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $lines[] = $label.': '.$value;
        }

        $lines[] = '';
        $lines[] = $idempotencyTag;

        return implode("\n", $lines);
    }
}
