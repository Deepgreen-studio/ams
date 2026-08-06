<?php

namespace App\Domains\Support\Services;

use App\Domains\Compliance\Enums\PrivacyRequestType;
use App\Domains\Compliance\Services\PrivacyRequestService;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Enums\SupportTicketWorkflowAction;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Repositories\SupportTicketRepository;
use App\Domains\Support\Repositories\SupportTicketStatusHistoryRepository;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * EasyCarbs-style triage: tickets involving personal data escalate to Compliance
 * and stay linked to the original Support ticket (non-breach DSARs → Privacy Request).
 */
class SupportComplianceRoutingService
{
    /**
     * Patterns that indicate personal-data / privacy handling (not operational account disable).
     *
     * @var list<string>
     */
    private const PERSONAL_DATA_PATTERNS = [
        'health information',
        'remove my health',
        'personal data',
        'gdpr',
        'data subject',
        'right to be forgotten',
        'erase my data',
        'delete my data',
        'remove my data',
        'data deletion',
        'data correction',
        'privacy request',
        'blood glucose',
        'blood pressure',
    ];

    /**
     * Patterns that stay in Support (operational), even if account-related.
     *
     * @var list<string>
     */
    private const SUPPORT_ONLY_PATTERNS = [
        'temporarily disable my account',
        'disable my account',
        'suspend my account',
        'deactivate my account',
    ];

    public function __construct(
        private readonly PrivacyRequestService $privacyRequestService,
        private readonly SupportTicketRepository $supportTicketRepository,
        private readonly SupportTicketStatusHistoryRepository $historyRepository,
    ) {}

    public function involvesPersonalData(SupportTicket $ticket, ?bool $explicitFlag = null): bool
    {
        if ($explicitFlag === true || $ticket->involves_personal_data === true) {
            return ! $this->isSupportOnlyOperationalRequest($ticket);
        }

        if ($this->isSupportOnlyOperationalRequest($ticket)) {
            return false;
        }

        $haystack = strtolower(trim(($ticket->subject ?? '').' '.($ticket->description ?? '')));

        foreach (self::PERSONAL_DATA_PATTERNS as $pattern) {
            if ($pattern !== '' && str_contains($haystack, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function shouldEscalateToCompliance(SupportTicket $ticket, ?bool $explicitFlag = null): bool
    {
        if ($ticket->privacy_request_id !== null || $ticket->compliance_routed_at !== null) {
            return false;
        }

        return $this->involvesPersonalData($ticket, $explicitFlag);
    }

    public function routeIfNeeded(SupportTicket $ticket, User $actor, ?bool $explicitFlag = null): SupportTicket
    {
        if (! $this->shouldEscalateToCompliance($ticket, $explicitFlag)) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $actor): SupportTicket {
            $ticket->refresh();
            if ($ticket->privacy_request_id !== null || $ticket->compliance_routed_at !== null) {
                return $ticket;
            }

            $customer = $ticket->customer;
            $requesterName = $customer
                ? (trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: ($customer->company_name ?: 'Requester'))
                : 'Requester';
            $requesterEmail = $customer?->email ?? ($actor->email ?: 'privacy@example.com');

            $privacyRequest = $this->privacyRequestService->create([
                'company_id' => $ticket->company?->uuid ?? (string) $ticket->company_id,
                'customer_id' => $customer?->uuid,
                'request_type' => $this->resolvePrivacyRequestType($ticket)->value,
                'requester_name' => $requesterName,
                'requester_email' => $requesterEmail,
                'requester_phone' => $customer?->phone,
                'description' => $this->buildPrivacyDescription($ticket),
                'assigned_to' => $actor->uuid,
                'support_ticket_id' => $ticket->id,
            ], $actor);

            $previousStatus = $ticket->status instanceof SupportTicketStatus
                ? $ticket->status
                : SupportTicketStatus::tryFrom((string) $ticket->status);

            $updated = $this->supportTicketRepository->updateTicket($ticket, [
                'involves_personal_data' => true,
                'compliance_routed_at' => now(),
                'privacy_request_id' => $privacyRequest->id,
                'status' => SupportTicketStatus::Pending->value,
                'updated_by' => $actor->id,
            ]);

            $privacyRequest->forceFill([
                'support_ticket_id' => $updated->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->historyRepository->recordForTicket(
                $updated,
                SupportTicketWorkflowAction::EscalatedToCompliance->value,
                $previousStatus?->value,
                SupportTicketStatus::Pending->value,
                $actor->id,
                'Personal data detected. Escalated to Compliance privacy request '.$privacyRequest->request_number.' (linked). Not classified as a data breach.',
                [
                    'privacy_request_uuid' => $privacyRequest->uuid,
                    'privacy_request_number' => $privacyRequest->request_number,
                    'breach_or_near_miss' => false,
                    'routing' => 'compliance_privacy_request',
                ]
            );

            return $updated->fresh(['privacyRequest', 'customer', 'company']);
        });
    }

    private function isSupportOnlyOperationalRequest(SupportTicket $ticket): bool
    {
        $haystack = strtolower(trim(($ticket->subject ?? '').' '.($ticket->description ?? '')));

        foreach (self::SUPPORT_ONLY_PATTERNS as $pattern) {
            if ($pattern !== '' && str_contains($haystack, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function resolvePrivacyRequestType(SupportTicket $ticket): PrivacyRequestType
    {
        $haystack = strtolower(($ticket->subject ?? '').' '.($ticket->description ?? ''));

        if (str_contains($haystack, 'delete') || str_contains($haystack, 'erase') || str_contains($haystack, 'forgotten')) {
            return PrivacyRequestType::DataDeletion;
        }

        if (str_contains($haystack, 'restrict') || str_contains($haystack, 'object')) {
            return PrivacyRequestType::RestrictProcessing;
        }

        return PrivacyRequestType::DataCorrection;
    }

    private function buildPrivacyDescription(SupportTicket $ticket): string
    {
        return trim(
            ($ticket->subject ?? '')."\n\n".($ticket->description ?? '')."\n\n"
            .'[Auto-routed from Support ticket '.$ticket->ticket_number.'. '
            .'Personal data path: Compliance privacy request. '
            .'Breach/near-miss assessment: No (data subject request, not an incident).]'
        );
    }
}
