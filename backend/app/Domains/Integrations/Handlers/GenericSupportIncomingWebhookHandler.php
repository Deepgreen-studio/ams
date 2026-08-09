<?php

namespace App\Domains\Integrations\Handlers;

use App\Domains\Applications\Models\Application;
use App\Domains\Integrations\Contracts\IncomingWebhookHandlerInterface;
use App\Domains\Integrations\Enums\WebsiteFormDestination;
use App\Domains\Integrations\Enums\WebsiteFormIntent;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Integrations\Services\WebsiteFormIngestService;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketMessageAuthorType;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Models\SupportTicketMessage;
use App\Domains\Support\Services\SupportTicketConversationService;
use App\Domains\Support\Services\SupportTicketService;
use App\Models\User;

/**
 * Generic ingest for ANY connected app/website.
 *
 * External apps POST signed payloads to their AMS incoming webhook URL using
 * the standard Support events below. No app-specific slug is required.
 *
 * Two-way SMS/chat:
 * - First message creates a Support ticket + customer Conversation bubble.
 * - Follow-ups with ticket_uuid (or matching open SMS phone thread) append
 *   to the same ticket Conversation instead of opening a new ticket.
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
        private readonly SupportTicketConversationService $conversationService,
        private readonly WebsiteFormIngestService $websiteFormIngestService,
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
        $formIntent = $this->resolveFormIntent($data, $eventName);

        $existingCompliance = $this->websiteFormIngestService->findExistingByIdempotencyTag(
            (int) $webhook->company_id,
            $idempotencyTag
        );
        if ($existingCompliance !== null) {
            return $existingCompliance;
        }

        $existing = $this->findExistingByIdempotencyTag((int) $webhook->company_id, $idempotencyTag);
        if ($existing !== null) {
            return [
                'handled' => true,
                'skipped' => true,
                'reason' => 'Idempotent skip — Support ticket already ingested this message.',
                'form_type' => $formIntent?->value,
                'destination' => $formIntent?->destination()->value,
                'support_ticket_uuid' => $existing->uuid,
                'support_ticket_number' => $existing->ticket_number,
                'privacy_request_uuid' => $existing->privacyRequest?->uuid,
                'privacy_request_number' => $existing->privacyRequest?->request_number,
                'actions' => ['idempotent_skip'],
            ];
        }

        $from = (string) ($data['from'] ?? $data['sender'] ?? $data['phone'] ?? '');
        $subject = trim((string) ($data['subject'] ?? ''));
        $source = $this->resolveSource($eventName, $data);

        if ($subject === '') {
            $subject = $this->defaultSubject($formIntent, $source, $from, $webhook);
        }

        // Compliance-only intents insert into Cases / Breaches / DPIA / Privacy — not Support.
        if ($formIntent !== null && ! $formIntent->createsSupportTicket()) {
            return $this->websiteFormIngestService->ingest(
                $formIntent,
                $webhook,
                $data,
                $subject,
                $body,
                $idempotencyTag,
                $actor,
            );
        }

        $threadTicket = $this->resolveThreadTicket((int) $webhook->company_id, $data, $source);

        if ($threadTicket !== null) {
            $this->appendCustomerMessage($threadTicket, $body, $actor);
            $this->rememberIdempotencyTag($threadTicket, $idempotencyTag);

            $threadTicket->loadMissing(['privacyRequest']);

            return [
                'handled' => true,
                'skipped' => false,
                'form_type' => $formIntent?->value,
                'destination' => $formIntent?->destination()->value ?? WebsiteFormDestination::Support->value,
                'support_ticket_uuid' => $threadTicket->uuid,
                'support_ticket_number' => $threadTicket->ticket_number,
                'privacy_request_uuid' => $threadTicket->privacyRequest?->uuid,
                'privacy_request_number' => $threadTicket->privacyRequest?->request_number,
                'actions' => ['support_ticket_message_appended'],
            ];
        }

        $involvesPersonalData = $this->resolveInvolvesPersonalData($data, $formIntent);
        $application = $this->resolveApplication($webhook, $data);

        $ticket = $this->supportTicketService->create([
            'company_id' => $webhook->company?->uuid ?? (string) $webhook->company_id,
            'application_id' => $application?->uuid,
            'subject' => mb_substr($subject, 0, 255),
            'description' => $this->buildDescription($eventName, $data, $payload, $idempotencyTag, $log, $webhook, $body, $formIntent),
            'category' => $this->resolveCategory($data)->value,
            'priority' => $this->resolvePriority($data)->value,
            'source' => $source->value,
            'involves_personal_data' => $involvesPersonalData,
        ], $actor);

        $this->appendCustomerMessage($ticket, $body, $actor);
        $ticket->loadMissing(['privacyRequest']);

        $actions = ['support_ticket_created', 'support_ticket_message_created'];
        if ($ticket->privacy_request_id !== null) {
            $actions[] = 'compliance_privacy_request_created';
        }

        $destination = $ticket->privacy_request_id !== null
            ? WebsiteFormDestination::SupportAndPrivacy
            : ($formIntent?->destination() ?? WebsiteFormDestination::Support);

        return [
            'handled' => true,
            'skipped' => false,
            'form_type' => $formIntent?->value,
            'destination' => $destination->value,
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
    private function resolveFormIntent(array $data, string $eventName): ?WebsiteFormIntent
    {
        $raw = $data['form_type'] ?? $data['website_intent'] ?? $data['intent'] ?? null;
        $intent = WebsiteFormIntent::tryFromAlias(is_string($raw) ? $raw : null);

        if ($intent !== null) {
            return $intent;
        }

        if ($eventName === 'support.sms.received') {
            return WebsiteFormIntent::Sms;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveInvolvesPersonalData(array $data, ?WebsiteFormIntent $formIntent): bool
    {
        if (array_key_exists('involves_personal_data', $data)) {
            return filter_var($data['involves_personal_data'], FILTER_VALIDATE_BOOLEAN);
        }

        return $formIntent?->involvesPersonalData() ?? false;
    }

    private function defaultSubject(
        ?WebsiteFormIntent $formIntent,
        SupportTicketSource $source,
        string $from,
        Webhook $webhook,
    ): string {
        if ($formIntent !== null) {
            return match ($formIntent) {
                WebsiteFormIntent::Complaint => 'Website complaint',
                WebsiteFormIntent::Privacy => 'Privacy / GDPR request',
                WebsiteFormIntent::AccountDisable => 'Disable account request',
                WebsiteFormIntent::Chat => 'Live chat message',
                WebsiteFormIntent::Sms => 'SMS support'.($from !== '' ? ' from '.$from : ''),
                default => 'Support message from '.($webhook->name ?: ($webhook->slug ?? 'connected app')),
            };
        }

        return $source === SupportTicketSource::Sms
            ? 'SMS support'.($from !== '' ? ' from '.$from : '')
            : 'Support message from '.($webhook->name ?: ($webhook->slug ?? 'connected app'));
    }

    private function appendCustomerMessage(SupportTicket $ticket, string $body, User $actor): void
    {
        $this->conversationService->createMessage(
            $ticket->uuid,
            [
                'body' => '<p>'.e($body).'</p>',
                'body_format' => 'html',
                'visibility' => SupportTicketMessageVisibility::Public->value,
                'author_type' => SupportTicketMessageAuthorType::Customer->value,
            ],
            $actor
        );
    }

    private function rememberIdempotencyTag(SupportTicket $ticket, string $idempotencyTag): void
    {
        $description = (string) $ticket->description;
        if (str_contains($description, $idempotencyTag)) {
            return;
        }

        $ticket->forceFill([
            'description' => rtrim($description)."\n".$idempotencyTag,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveThreadTicket(int $companyId, array $data, SupportTicketSource $source): ?SupportTicket
    {
        $ticketUuid = trim((string) ($data['ticket_uuid'] ?? $data['support_ticket_uuid'] ?? ''));
        if ($ticketUuid !== '') {
            $byUuid = SupportTicket::query()
                ->where('company_id', $companyId)
                ->where('uuid', $ticketUuid)
                ->with(['privacyRequest'])
                ->first();

            if ($byUuid !== null) {
                return $byUuid;
            }
        }

        $ticketNumber = trim((string) ($data['ticket_number'] ?? $data['support_ticket_number'] ?? ''));
        if ($ticketNumber !== '') {
            $byNumber = SupportTicket::query()
                ->where('company_id', $companyId)
                ->where('ticket_number', $ticketNumber)
                ->with(['privacyRequest'])
                ->first();

            if ($byNumber !== null) {
                return $byNumber;
            }
        }

        if ($source !== SupportTicketSource::Sms) {
            return null;
        }

        $from = trim((string) ($data['from'] ?? $data['sender'] ?? $data['customer_phone'] ?? $data['phone'] ?? ''));
        if ($from === '') {
            return null;
        }

        $closed = [
            SupportTicketStatus::Closed->value,
            SupportTicketStatus::Cancelled->value,
        ];

        return SupportTicket::query()
            ->where('company_id', $companyId)
            ->where('source', SupportTicketSource::Sms->value)
            ->whereNotIn('status', $closed)
            ->where(function ($query) use ($from): void {
                $query->where('description', 'like', '%from: '.$from.'%')
                    ->orWhere('description', 'like', '%customer_phone: '.$from.'%');
            })
            ->with(['privacyRequest'])
            ->latest('id')
            ->first();
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

    private function findExistingByIdempotencyTag(int $companyId, string $idempotencyTag): ?SupportTicket
    {
        $byDescription = SupportTicket::query()
            ->where('company_id', $companyId)
            ->where('description', 'like', '%'.$idempotencyTag.'%')
            ->with(['privacyRequest'])
            ->first();

        if ($byDescription !== null) {
            return $byDescription;
        }

        $message = SupportTicketMessage::query()
            ->where('company_id', $companyId)
            ->where('body', 'like', '%'.$idempotencyTag.'%')
            ->with(['ticket.privacyRequest'])
            ->first();

        return $message?->ticket;
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
        ?WebsiteFormIntent $formIntent = null,
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

        if ($formIntent !== null) {
            $lines[] = 'form_type: '.$formIntent->value;
            $lines[] = 'destination: '.$formIntent->destination()->value;
        }

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
