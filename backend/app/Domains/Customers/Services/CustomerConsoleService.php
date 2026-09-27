<?php

namespace App\Domains\Customers\Services;

use App\Domains\Applications\Models\ApplicationHealthMetric;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerApplication;
use App\Domains\Support\Enums\SupportSlaStatus;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use Illuminate\Support\Collection;

class CustomerConsoleService
{
    /**
     * Operational customer view. Subscription commercial review stays on the
     * subscription module; this console only counts and links those records.
     *
     * @return array<string, mixed>
     */
    public function show(Customer $customer): array
    {
        $customer->loadCount([
            'contacts',
            'applications',
            'subscriptions',
            'licenses',
            'documents',
            'communications',
            'privacyRequests',
        ]);

        $assignments = $customer->applications()
            ->with([
                'application:id,uuid,name,platform,status',
                'environment:id,uuid,name,type,status',
                'version:id,uuid,version_number,build_number',
                'release:id,uuid,name,version_label,status',
                'slaPolicy:id,uuid,name',
            ])
            ->latest('id')
            ->limit(25)
            ->get();

        $openStatuses = [
            SupportTicketStatus::Open->value,
            SupportTicketStatus::Pending->value,
            SupportTicketStatus::InProgress->value,
            SupportTicketStatus::WaitingForCustomer->value,
            SupportTicketStatus::Reopened->value,
        ];

        $tickets = SupportTicket::query()
            ->where('customer_id', $customer->id)
            ->with([
                'application:id,uuid,name',
                'customerApplication:id,uuid,assignment_number',
                'team:id,name',
                'assignee:id,full_name',
            ])
            ->latest('id')
            ->limit(50)
            ->get();

        $openTickets = $tickets->filter(
            fn (SupportTicket $ticket): bool => in_array($ticket->status?->value ?? $ticket->status, $openStatuses, true)
        );
        $criticalTickets = $openTickets->filter(function (SupportTicket $ticket): bool {
            $priority = $ticket->priority?->value ?? $ticket->priority;
            $category = $ticket->category?->value ?? $ticket->category;

            return in_array($priority, [
                SupportTicketPriority::Critical->value,
                SupportTicketPriority::Emergency->value,
            ], true) || in_array($category, [
                SupportTicketCategory::BugReport->value,
                SupportTicketCategory::EmergencySupport->value,
            ], true);
        });
        $breaches = $tickets->filter(
            fn (SupportTicket $ticket): bool => ($ticket->sla_status?->value ?? $ticket->sla_status) === SupportSlaStatus::Breached->value
        );

        $privacyRequests = PrivacyRequest::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->limit(20)
            ->get(['id', 'uuid', 'request_number', 'request_type', 'status', 'due_date', 'created_at']);

        return [
            'model' => [
                'company_owns_applications' => true,
                'customer_is_entitled' => true,
                'ownership_note' => 'The company owns applications. A customer is an individual or an organization assigned to those applications. Assignment ownership is operational responsibility, not application ownership.',
            ],
            'counts' => [
                'contacts' => $customer->contacts_count,
                'applications' => $customer->applications_count,
                'subscriptions' => $customer->subscriptions_count,
                'licenses' => $customer->licenses_count,
                'documents' => $customer->documents_count,
                'communications' => $customer->communications_count,
                'open_tickets' => $openTickets->count(),
                'critical_issues' => $criticalTickets->count(),
                'sla_breaches' => $breaches->count(),
                'privacy_requests' => $customer->privacy_requests_count,
            ],
            'assignments' => $assignments->map(fn (CustomerApplication $assignment): array => $this->assignmentSummary($assignment))->values(),
            'support' => [
                'open_tickets' => $openTickets->take(10)->map(fn (SupportTicket $ticket): array => $this->ticketSummary($ticket))->values(),
                'critical_issues' => $criticalTickets->take(10)->map(fn (SupportTicket $ticket): array => $this->ticketSummary($ticket))->values(),
                'sla_breaches' => $breaches->take(10)->map(fn (SupportTicket $ticket): array => $this->ticketSummary($ticket))->values(),
                'teams' => $openTickets->pluck('team.name')->filter()->unique()->values(),
                'resolved_count' => $tickets->filter(
                    fn (SupportTicket $ticket): bool => in_array($ticket->status?->value ?? $ticket->status, [
                        SupportTicketStatus::Resolved->value,
                        SupportTicketStatus::Closed->value,
                    ], true)
                )->count(),
            ],
            'privacy' => [
                'legal_basis' => $customer->legal_basis?->value ?? $customer->legal_basis,
                'legal_basis_label' => $customer->legal_basis?->label(),
                'processing_purpose' => $customer->processing_purpose,
                'retention_until' => $customer->retention_until?->toDateString(),
                'anonymized_at' => $customer->anonymized_at,
                'requests' => $privacyRequests->map(fn (PrivacyRequest $request): array => [
                    'uuid' => $request->uuid,
                    'request_number' => $request->request_number,
                    'request_type' => $request->request_type?->value ?? $request->request_type,
                    'status' => $request->status?->value ?? $request->status,
                    'due_date' => $request->due_date,
                    'created_at' => $request->created_at,
                ])->values(),
            ],
            'health' => $this->healthForAssignments($assignments),
            'activity' => $this->recentActivity($customer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function assignmentSummary(CustomerApplication $assignment): array
    {
        return [
            'uuid' => $assignment->uuid,
            'assignment_number' => $assignment->assignment_number,
            'platform' => $assignment->platform
                ?: ($assignment->application?->platform?->value ?? $assignment->application?->platform),
            'ownership_type' => $assignment->ownership_type?->value ?? $assignment->ownership_type,
            'ownership_label' => $assignment->ownership_type?->label(),
            'ownership_description' => $assignment->ownership_type?->description(),
            'status' => $assignment->status?->value ?? $assignment->status,
            'application' => $assignment->application ? [
                'uuid' => $assignment->application->uuid,
                'name' => $assignment->application->name,
            ] : null,
            'environment' => $assignment->environment ? [
                'uuid' => $assignment->environment->uuid,
                'name' => $assignment->environment->name,
            ] : null,
            'version' => $assignment->version ? [
                'uuid' => $assignment->version->uuid,
                'version_number' => $assignment->version->version_number,
                'build_number' => $assignment->version->build_number,
            ] : null,
            'build_label' => $assignment->build_label,
            'release' => $assignment->release ? [
                'uuid' => $assignment->release->uuid,
                'name' => $assignment->release->name,
                'version_label' => $assignment->release->version_label,
            ] : null,
            'sla' => $assignment->slaPolicy ? [
                'uuid' => $assignment->slaPolicy->uuid,
                'name' => $assignment->slaPolicy->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function ticketSummary(SupportTicket $ticket): array
    {
        return [
            'uuid' => $ticket->uuid,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority?->value ?? $ticket->priority,
            'status' => $ticket->status?->value ?? $ticket->status,
            'category' => $ticket->category?->value ?? $ticket->category,
            'sla_status' => $ticket->sla_status?->value ?? $ticket->sla_status,
            'application' => $ticket->application ? [
                'uuid' => $ticket->application->uuid,
                'name' => $ticket->application->name,
            ] : null,
            'assignment_number' => $ticket->customerApplication?->assignment_number,
            'team' => $ticket->team?->name,
            'assignee' => $ticket->assignee?->full_name,
        ];
    }

    /**
     * @param Collection<int, CustomerApplication> $assignments
     * @return list<array<string, mixed>>
     */
    protected function healthForAssignments(Collection $assignments): array
    {
        if ($assignments->isEmpty()) {
            return [];
        }

        $applicationIds = $assignments->pluck('application_id')->filter()->unique()->values();
        $metrics = ApplicationHealthMetric::query()
            ->whereIn('application_id', $applicationIds)
            ->orderByDesc('recorded_at')
            ->get()
            ->unique(fn (ApplicationHealthMetric $metric): string => $metric->application_id . '-' . ($metric->environment_id ?? 'none'));

        return $assignments->map(function (CustomerApplication $assignment) use ($metrics): array {
            $metric = $metrics->first(function (ApplicationHealthMetric $metric) use ($assignment): bool {
                if ((int) $metric->application_id !== (int) $assignment->application_id) {
                    return false;
                }

                if ($assignment->application_environment_id === null) {
                    return true;
                }

                return (int) $metric->environment_id === (int) $assignment->application_environment_id
                    || $metric->environment_id === null;
            });

            return [
                'assignment_uuid' => $assignment->uuid,
                'assignment_number' => $assignment->assignment_number,
                'application' => $assignment->application?->name,
                'environment' => $assignment->environment?->name,
                'version' => $assignment->version?->version_number ?? $metric?->version_label,
                'platform' => $assignment->platform
                    ?: ($assignment->application?->platform?->value ?? $assignment->application?->platform),
                'health_score' => $metric?->health_score,
                'api_error_rate' => $metric?->api_error_rate,
                'crash_rate' => $metric?->crash_rate,
                'recorded_at' => $metric?->recorded_at,
                'source' => $metric ? 'integration_monitoring' : null,
            ];
        })->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function recentActivity(Customer $customer): array
    {
        $activityModel = config('activitylog.activity_model');

        return $activityModel::query()
            ->where('subject_type', $customer->getMorphClass())
            ->where('subject_id', $customer->id)
            ->with('causer:id,full_name')
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(fn ($activity): array => [
                'id' => $activity->id,
                'description' => $activity->description,
                'event' => $activity->event,
                'created_at' => $activity->created_at,
                'causer' => $activity->causer?->full_name,
            ])
            ->all();
    }
}
