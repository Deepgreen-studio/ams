<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Compliance\Enums\ComplianceCasePriority;
use App\Domains\Compliance\Enums\ComplianceCaseType;
use App\Domains\Compliance\Enums\DataBreachSeverity;
use App\Domains\Compliance\Enums\DataBreachType;
use App\Domains\Compliance\Enums\PrivacyRequestType;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Domains\Compliance\Models\DataBreach;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Compliance\Services\ComplianceCaseService;
use App\Domains\Compliance\Services\DataBreachService;
use App\Domains\Compliance\Services\DpiaService;
use App\Domains\Compliance\Services\PrivacyRequestService;
use App\Domains\Integrations\Enums\WebsiteFormDestination;
use App\Domains\Integrations\Enums\WebsiteFormIntent;
use App\Domains\Integrations\Models\Webhook;
use App\Models\User;

/**
 * Creates Compliance records for website form intents that must NOT open a Support ticket.
 */
class WebsiteFormIngestService
{
    public function __construct(
        private readonly ComplianceCaseService $complianceCaseService,
        private readonly DataBreachService $dataBreachService,
        private readonly DpiaService $dpiaService,
        private readonly PrivacyRequestService $privacyRequestService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null  Existing ingest result when idempotent, otherwise null
     */
    public function findExistingByIdempotencyTag(int $companyId, string $idempotencyTag): ?array
    {
        $privacy = PrivacyRequest::query()
            ->where('company_id', $companyId)
            ->where('description', 'like', '%'.$idempotencyTag.'%')
            ->first();

        if ($privacy !== null) {
            return [
                'handled' => true,
                'skipped' => true,
                'reason' => 'Idempotent skip — Privacy Request already ingested this message.',
                'form_type' => WebsiteFormIntent::Consent->value,
                'destination' => WebsiteFormDestination::PrivacyOnly->value,
                'privacy_request_uuid' => $privacy->uuid,
                'privacy_request_number' => $privacy->request_number,
                'actions' => ['idempotent_skip'],
            ];
        }

        $case = ComplianceCase::query()
            ->where('company_id', $companyId)
            ->where('description', 'like', '%'.$idempotencyTag.'%')
            ->first();

        if ($case !== null) {
            return [
                'handled' => true,
                'skipped' => true,
                'reason' => 'Idempotent skip — Compliance Case already ingested this message.',
                'form_type' => WebsiteFormIntent::ComplianceCase->value,
                'destination' => WebsiteFormDestination::ComplianceCase->value,
                'compliance_case_uuid' => $case->uuid,
                'compliance_case_number' => $case->case_number,
                'actions' => ['idempotent_skip'],
            ];
        }

        $breach = DataBreach::query()
            ->where('company_id', $companyId)
            ->where('description', 'like', '%'.$idempotencyTag.'%')
            ->first();

        if ($breach !== null) {
            return [
                'handled' => true,
                'skipped' => true,
                'reason' => 'Idempotent skip — Data Breach already ingested this message.',
                'form_type' => WebsiteFormIntent::Breach->value,
                'destination' => WebsiteFormDestination::Breach->value,
                'data_breach_uuid' => $breach->uuid,
                'data_breach_number' => $breach->breach_number,
                'actions' => ['idempotent_skip'],
            ];
        }

        $dpia = DpiaAssessment::query()
            ->where('company_id', $companyId)
            ->where('description', 'like', '%'.$idempotencyTag.'%')
            ->first();

        if ($dpia !== null) {
            return [
                'handled' => true,
                'skipped' => true,
                'reason' => 'Idempotent skip — DPIA already ingested this message.',
                'form_type' => WebsiteFormIntent::Dpia->value,
                'destination' => WebsiteFormDestination::Dpia->value,
                'dpia_assessment_uuid' => $dpia->uuid,
                'dpia_assessment_number' => $dpia->assessment_number,
                'actions' => ['idempotent_skip'],
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function ingest(
        WebsiteFormIntent $intent,
        Webhook $webhook,
        array $data,
        string $subject,
        string $body,
        string $idempotencyTag,
        User $actor,
    ): array {
        $destination = $intent->destination();
        $companyId = $webhook->company?->uuid ?? (string) $webhook->company_id;
        $description = $this->buildDescription($intent, $subject, $body, $data, $idempotencyTag, $webhook);

        return match ($destination) {
            WebsiteFormDestination::PrivacyOnly => $this->createPrivacyOnly(
                $intent,
                $companyId,
                $subject,
                $description,
                $data,
                $actor,
            ),
            WebsiteFormDestination::ComplianceCase => $this->createComplianceCase(
                $intent,
                $companyId,
                $subject,
                $description,
                $data,
                $actor,
            ),
            WebsiteFormDestination::Breach => $this->createBreach(
                $intent,
                $companyId,
                $subject,
                $description,
                $data,
                $actor,
            ),
            WebsiteFormDestination::Dpia => $this->createDpia(
                $intent,
                $companyId,
                $subject,
                $description,
                $data,
                $actor,
            ),
            default => [
                'handled' => false,
                'skipped' => true,
                'reason' => 'Destination requires Support ticket path: '.$destination->value,
                'actions' => [],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function createPrivacyOnly(
        WebsiteFormIntent $intent,
        string $companyId,
        string $subject,
        string $description,
        array $data,
        User $actor,
    ): array {
        $requesterName = trim((string) ($data['customer_name'] ?? $data['name'] ?? ''));
        if ($requesterName === '') {
            $requesterName = 'Website requester';
        }

        $requesterEmail = trim((string) ($data['customer_email'] ?? $data['email'] ?? ''));
        if ($requesterEmail === '') {
            $requesterEmail = $actor->email ?: 'privacy@example.com';
        }

        $privacy = $this->privacyRequestService->create([
            'company_id' => $companyId,
            'request_type' => PrivacyRequestType::ConsentWithdrawal->value,
            'requester_name' => $requesterName,
            'requester_email' => $requesterEmail,
            'requester_phone' => $data['customer_phone'] ?? $data['phone'] ?? $data['from'] ?? null,
            'description' => $description,
            'assigned_to' => $actor->uuid,
        ], $actor);

        return [
            'handled' => true,
            'skipped' => false,
            'form_type' => $intent->value,
            'destination' => WebsiteFormDestination::PrivacyOnly->value,
            'privacy_request_uuid' => $privacy->uuid,
            'privacy_request_number' => $privacy->request_number,
            'actions' => ['compliance_privacy_request_created'],
            'subject' => $subject,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function createComplianceCase(
        WebsiteFormIntent $intent,
        string $companyId,
        string $subject,
        string $description,
        array $data,
        User $actor,
    ): array {
        $priorityRaw = strtolower((string) ($data['priority'] ?? 'medium'));
        $priority = ComplianceCasePriority::tryFrom($priorityRaw)
            ?? match ($priorityRaw) {
                'urgent', 'emergency', 'critical' => ComplianceCasePriority::Critical,
                'high' => ComplianceCasePriority::High,
                'low' => ComplianceCasePriority::Low,
                default => ComplianceCasePriority::Medium,
            };

        $case = $this->complianceCaseService->create([
            'company_id' => $companyId,
            'title' => mb_substr($subject !== '' ? $subject : 'Website compliance case', 0, 255),
            'description' => $description,
            'case_type' => ComplianceCaseType::ComplianceCase->value,
            'priority' => $priority->value,
            'assigned_to' => $actor->uuid,
        ], $actor);

        return [
            'handled' => true,
            'skipped' => false,
            'form_type' => $intent->value,
            'destination' => WebsiteFormDestination::ComplianceCase->value,
            'compliance_case_uuid' => $case->uuid,
            'compliance_case_number' => $case->case_number,
            'actions' => ['compliance_case_created'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function createBreach(
        WebsiteFormIntent $intent,
        string $companyId,
        string $subject,
        string $description,
        array $data,
        User $actor,
    ): array {
        $severityRaw = strtolower((string) ($data['severity'] ?? $data['priority'] ?? 'medium'));
        $severity = DataBreachSeverity::tryFrom($severityRaw)
            ?? match ($severityRaw) {
                'urgent', 'emergency', 'critical' => DataBreachSeverity::Critical,
                'high' => DataBreachSeverity::High,
                'low' => DataBreachSeverity::Low,
                default => DataBreachSeverity::Medium,
            };

        $breachType = DataBreachType::tryFrom((string) ($data['breach_type'] ?? ''))
            ?? DataBreachType::Other;

        $breach = $this->dataBreachService->create([
            'company_id' => $companyId,
            'title' => mb_substr($subject !== '' ? $subject : 'Website data breach report', 0, 255),
            'description' => $description,
            'breach_type' => $breachType->value,
            'severity' => $severity->value,
            'personal_data_involved' => true,
            'assigned_to' => $actor->uuid,
        ], $actor);

        return [
            'handled' => true,
            'skipped' => false,
            'form_type' => $intent->value,
            'destination' => WebsiteFormDestination::Breach->value,
            'data_breach_uuid' => $breach->uuid,
            'data_breach_number' => $breach->breach_number,
            'actions' => ['compliance_breach_created'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function createDpia(
        WebsiteFormIntent $intent,
        string $companyId,
        string $subject,
        string $description,
        array $data,
        User $actor,
    ): array {
        $assessment = $this->dpiaService->createAssessment([
            'company_id' => $companyId,
            'title' => mb_substr($subject !== '' ? $subject : 'Website DPIA request', 0, 255),
            'description' => $description,
            'assigned_to' => $actor->uuid,
        ], $actor);

        return [
            'handled' => true,
            'skipped' => false,
            'form_type' => $intent->value,
            'destination' => WebsiteFormDestination::Dpia->value,
            'dpia_assessment_uuid' => $assessment->uuid,
            'dpia_assessment_number' => $assessment->assessment_number,
            'actions' => ['compliance_dpia_created'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildDescription(
        WebsiteFormIntent $intent,
        string $subject,
        string $body,
        array $data,
        string $idempotencyTag,
        Webhook $webhook,
    ): string {
        $lines = [
            $body,
            '',
            '---',
            'Auto-ingested from website form (no Support ticket).',
            'Form type: '.$intent->value.' ('.$intent->label().')',
            'Destination: '.$intent->destination()->label(),
            'App / webhook: '.($webhook->name ?: ($webhook->slug ?? 'n/a')),
        ];

        if ($subject !== '') {
            $lines[] = 'Subject: '.$subject;
        }

        foreach ([
            'customer_name' => $data['customer_name'] ?? $data['name'] ?? null,
            'customer_email' => $data['customer_email'] ?? $data['email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? $data['phone'] ?? $data['from'] ?? null,
            'message_id' => $data['message_id'] ?? $data['external_id'] ?? null,
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
