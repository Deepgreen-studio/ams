<?php

namespace App\Domains\Support\Listeners;

use App\Domains\Integrations\Services\WebhookService;
use App\Domains\Support\Enums\SupportTicketMessageAuthorType;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Events\SupportTicketMessageCreated;
use App\Domains\Support\Models\SupportTicket;

class DispatchSupportReplyOutgoingWebhook
{
    public function __construct(private readonly WebhookService $webhookService) {}

    public function handle(SupportTicketMessageCreated $event): void
    {
        $message = $event->message->loadMissing(['ticket.application', 'author']);
        $ticket = $message->ticket;

        if (! $ticket instanceof SupportTicket) {
            return;
        }

        $visibility = $message->visibility instanceof SupportTicketMessageVisibility
            ? $message->visibility
            : SupportTicketMessageVisibility::tryFrom((string) $message->visibility);

        $authorType = $message->author_type instanceof SupportTicketMessageAuthorType
            ? $message->author_type
            : SupportTicketMessageAuthorType::tryFrom((string) $message->author_type);

        if ($visibility !== SupportTicketMessageVisibility::Public) {
            return;
        }

        if ($authorType !== SupportTicketMessageAuthorType::Agent) {
            return;
        }

        $bodyHtml = (string) $message->body;
        $bodyPlain = trim(html_entity_decode(strip_tags($bodyHtml)));
        if ($bodyPlain === '') {
            return;
        }

        $source = $ticket->source instanceof SupportTicketSource
            ? $ticket->source->value
            : (string) $ticket->source;

        $parsed = $this->parseIngestMeta((string) $ticket->description);
        $customerPhone = $parsed['customer_phone'] ?? $parsed['from'] ?? null;
        $supportPhone = $parsed['to'] ?? null;
        $externalMessageId = $parsed['message_id'] ?? null;

        $channel = $source === 'sms' ? 'sms' : ($source ?: 'api');
        // Support / complaint channels deliver like live chat; SMS delivers like SMS.
        $replyMode = $channel === 'sms' ? 'sms' : 'live_chat';

        $payload = [
            'ticket_uuid' => $ticket->uuid,
            'ticket_number' => $ticket->ticket_number,
            'message_uuid' => $message->uuid,
            'message_id' => $message->uuid,
            'visibility' => 'public',
            'author_type' => 'agent',
            'body' => $bodyHtml,
            'body_plain' => $bodyPlain,
            'channel' => $channel,
            'source' => $source ?: 'api',
            'reply_mode' => $replyMode,
            'customer_phone' => $customerPhone,
            'from' => $supportPhone,
            'to' => $customerPhone,
            'application_slug' => $ticket->application?->slug,
            'external_message_id' => $externalMessageId,
            'category' => $ticket->category instanceof \BackedEnum
                ? $ticket->category->value
                : (string) ($ticket->category ?? ''),
        ];

        // Deliver immediately so connected apps (EasyCare) show replies without
        // requiring a separate queue worker during local/dev demos.
        $sync = ! app()->environment('production');

        $this->webhookService->dispatchEvent(
            'support.reply.sent',
            $payload,
            (int) $ticket->company_id,
            $event->actor,
            $sync,
        );

        if ($source === 'sms') {
            $this->webhookService->dispatchEvent(
                'support.sms.sent',
                $payload,
                (int) $ticket->company_id,
                $event->actor,
                $sync,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    protected function parseIngestMeta(string $description): array
    {
        $meta = [];

        foreach (['from', 'to', 'customer_phone', 'customer_email', 'customer_name', 'message_id'] as $key) {
            if (preg_match('/(?:^|\n)'.$key.':\s*(.+?)(?:\n|$)/i', $description, $m)) {
                $meta[$key] = trim($m[1]);
            }
        }

        if (preg_match('/\[ams-support-ingest:[^:]+:[^:]+:([^\]]+)\]/', $description, $m)) {
            $meta['message_id'] = $meta['message_id'] ?? trim($m[1]);
        }

        return $meta;
    }
}
