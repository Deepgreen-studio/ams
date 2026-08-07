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
 * Maps EasyCare domain webhooks into AMS Support tickets.
 * Personal-health events set involves_personal_data so SupportComplianceRouting
 * auto-creates a Compliance privacy request.
 */
class EasyCareIncomingWebhookHandler implements IncomingWebhookHandlerInterface
{
    public const SLUG = 'easycare';

    public const APPLICATION_SLUG = 'easycare-web';

    /**
     * Events that create Support tickets (and may escalate to Compliance).
     *
     * @var list<string>
     */
    private const HANDLED_EVENTS = [
        'user.created',
        'user.updated',
        'patient.created',
        'patient.updated',
        'appointment.created',
        'blood_sugar.created',
        'medicine.updated',
        'easycare.test',
    ];

    /**
     * Events involving special-category / health personal data → Compliance route.
     *
     * @var list<string>
     */
    private const PERSONAL_DATA_EVENTS = [
        'patient.created',
        'patient.updated',
        'blood_sugar.created',
        'medicine.updated',
    ];

    public function __construct(
        private readonly SupportTicketService $supportTicketService,
    ) {}

    public function supports(Webhook $webhook): bool
    {
        return $webhook->slug === self::SLUG;
    }

    public function handle(Webhook $webhook, WebhookLog $log, array $payload, User $actor): array
    {
        $eventName = (string) ($payload['event'] ?? $payload['event_name'] ?? $log->event_name ?? 'incoming.webhook');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if (! in_array($eventName, self::HANDLED_EVENTS, true)) {
            return [
                'handled' => false,
                'skipped' => true,
                'reason' => 'Event not mapped for EasyCare auto-ingest: '.$eventName,
                'actions' => [],
            ];
        }

        $externalId = $this->resolveExternalId($data, $log);
        $idempotencyTag = $this->idempotencyTag($eventName, $externalId);

        $existing = $this->findExistingTicket((int) $webhook->company_id, $idempotencyTag);
        if ($existing !== null) {
            return [
                'handled' => true,
                'skipped' => true,
                'reason' => 'Idempotent skip — ticket already exists for this EasyCare event.',
                'support_ticket_uuid' => $existing->uuid,
                'support_ticket_number' => $existing->ticket_number,
                'privacy_request_uuid' => $existing->privacyRequest?->uuid,
                'privacy_request_number' => $existing->privacyRequest?->request_number,
                'actions' => ['idempotent_skip'],
            ];
        }

        $involvesPersonalData = in_array($eventName, self::PERSONAL_DATA_EVENTS, true);
        $application = $this->resolveApplication((int) $webhook->company_id);

        $ticket = $this->supportTicketService->create([
            'company_id' => $webhook->company?->uuid ?? (string) $webhook->company_id,
            'application_id' => $application?->uuid,
            'subject' => $this->buildSubject($eventName, $data),
            'description' => $this->buildDescription($eventName, $data, $payload, $idempotencyTag, $log),
            'category' => $this->resolveCategory($eventName)->value,
            'priority' => $this->resolvePriority($eventName, $data)->value,
            'source' => SupportTicketSource::Api->value,
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
        $id = $data['uuid'] ?? $data['id'] ?? null;

        if ($id !== null && $id !== '') {
            return (string) $id;
        }

        return 'log-'.$log->uuid;
    }

    private function idempotencyTag(string $eventName, string $externalId): string
    {
        return '[easycare-ingest:'.$eventName.':'.$externalId.']';
    }

    private function findExistingTicket(int $companyId, string $idempotencyTag): ?SupportTicket
    {
        return SupportTicket::query()
            ->where('company_id', $companyId)
            ->where('description', 'like', '%'.$idempotencyTag.'%')
            ->with(['privacyRequest'])
            ->first();
    }

    private function resolveApplication(int $companyId): ?Application
    {
        return Application::query()
            ->where('company_id', $companyId)
            ->where('slug', self::APPLICATION_SLUG)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildSubject(string $eventName, array $data): string
    {
        return match ($eventName) {
            'user.created' => 'EasyCare: New user '.($data['email'] ?? $data['name'] ?? $data['uuid'] ?? ''),
            'user.updated' => 'EasyCare: User updated '.($data['email'] ?? $data['name'] ?? $data['uuid'] ?? ''),
            'patient.created' => 'EasyCare: Patient registered '.($data['medical_record_number'] ?? $data['uuid'] ?? ''),
            'patient.updated' => 'EasyCare: Patient updated '.($data['medical_record_number'] ?? $data['uuid'] ?? ''),
            'appointment.created' => 'EasyCare: Appointment scheduled '.($data['uuid'] ?? ''),
            'blood_sugar.created' => 'EasyCare: Blood sugar reading '.($data['value_mg_dl'] ?? '').' '.($data['unit'] ?? 'mg/dL'),
            'medicine.updated' => 'EasyCare: Medicine updated '.($data['name'] ?? $data['uuid'] ?? ''),
            'easycare.test' => 'EasyCare: Test webhook',
            default => 'EasyCare event: '.$eventName,
        };
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
    ): string {
        $lines = [
            'Auto-ingested from EasyCare incoming webhook.',
            'Event: '.$eventName,
            'Webhook log: '.$log->uuid,
            'Received at: '.($payload['timestamp'] ?? now()->toIso8601String()),
            '',
            'Payload summary:',
        ];

        foreach ($this->summaryFields($eventName, $data) as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $lines[] = '- '.$label.': '.$value;
        }

        if ($eventName === 'easycare.test') {
            $lines[] = '- message: '.(string) ($data['message'] ?? 'Test webhook from EasyCare');
        }

        $lines[] = '';
        $lines[] = $idempotencyTag;

        if (in_array($eventName, self::PERSONAL_DATA_EVENTS, true)) {
            $lines[] = '';
            $lines[] = 'Personal / health data involved. Routed for Compliance review when applicable.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    private function summaryFields(string $eventName, array $data): array
    {
        return match ($eventName) {
            'user.created', 'user.updated' => [
                'name' => (string) ($data['name'] ?? trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''))),
                'email' => isset($data['email']) ? (string) $data['email'] : null,
                'phone' => isset($data['phone']) ? (string) $data['phone'] : null,
                'role' => isset($data['role']) ? (string) $data['role'] : null,
                'uuid' => isset($data['uuid']) ? (string) $data['uuid'] : null,
            ],
            'patient.created', 'patient.updated' => [
                'MRN' => isset($data['medical_record_number']) ? (string) $data['medical_record_number'] : null,
                'user_id' => isset($data['user_id']) ? (string) $data['user_id'] : null,
                'has_diabetes' => array_key_exists('has_diabetes', $data) ? (string) (int) (bool) $data['has_diabetes'] : null,
                'diabetes_type' => isset($data['diabetes_type']) ? (string) $data['diabetes_type'] : null,
                'uuid' => isset($data['uuid']) ? (string) $data['uuid'] : null,
            ],
            'appointment.created' => [
                'patient_id' => isset($data['patient_id']) ? (string) $data['patient_id'] : null,
                'doctor_id' => isset($data['doctor_id']) ? (string) $data['doctor_id'] : null,
                'scheduled_at' => isset($data['scheduled_at']) ? (string) $data['scheduled_at'] : null,
                'type' => isset($data['type']) ? (string) $data['type'] : null,
                'status' => isset($data['status']) ? (string) $data['status'] : null,
                'uuid' => isset($data['uuid']) ? (string) $data['uuid'] : null,
            ],
            'blood_sugar.created' => [
                'patient_id' => isset($data['patient_id']) ? (string) $data['patient_id'] : null,
                'value' => isset($data['value_mg_dl']) ? (string) $data['value_mg_dl'] : null,
                'unit' => isset($data['unit']) ? (string) $data['unit'] : null,
                'measurement_type' => isset($data['measurement_type']) ? (string) $data['measurement_type'] : null,
                'measured_at' => isset($data['measured_at']) ? (string) $data['measured_at'] : null,
                'uuid' => isset($data['uuid']) ? (string) $data['uuid'] : null,
            ],
            'medicine.updated' => [
                'name' => isset($data['name']) ? (string) $data['name'] : null,
                'dosage' => isset($data['dosage']) ? (string) $data['dosage'] : null,
                'patient_id' => isset($data['patient_id']) ? (string) $data['patient_id'] : null,
                'is_active' => array_key_exists('is_active', $data) ? (string) (int) (bool) $data['is_active'] : null,
                'uuid' => isset($data['uuid']) ? (string) $data['uuid'] : null,
            ],
            default => [
                'uuid' => isset($data['uuid']) ? (string) $data['uuid'] : null,
                'id' => isset($data['id']) ? (string) $data['id'] : null,
            ],
        };
    }

    private function resolveCategory(string $eventName): SupportTicketCategory
    {
        return match ($eventName) {
            'user.created', 'user.updated' => SupportTicketCategory::CustomerSupport,
            'appointment.created' => SupportTicketCategory::GeneralInquiry,
            'blood_sugar.created', 'medicine.updated', 'patient.created', 'patient.updated' => SupportTicketCategory::TechnicalSupport,
            'easycare.test' => SupportTicketCategory::GeneralInquiry,
            default => SupportTicketCategory::GeneralInquiry,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePriority(string $eventName, array $data): SupportTicketPriority
    {
        if ($eventName === 'blood_sugar.created') {
            $value = (float) ($data['value_mg_dl'] ?? 0);
            if ($value > 0 && ($value < 54 || $value > 400)) {
                return SupportTicketPriority::Critical;
            }
            if ($value > 0 && ($value < 70 || $value > 250)) {
                return SupportTicketPriority::High;
            }
        }

        return match ($eventName) {
            'patient.created' => SupportTicketPriority::Medium,
            'medicine.updated' => SupportTicketPriority::Medium,
            'user.updated', 'easycare.test' => SupportTicketPriority::Low,
            default => SupportTicketPriority::Medium,
        };
    }
}
