<?php

namespace App\Domains\Support\Services;

use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Domains\Support\Enums\SupportTicketAssignmentType;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Enums\SupportTicketWorkflowAction;
use App\Domains\Support\Events\SupportTicketAssigned;
use App\Domains\Support\Events\SupportTicketClosed;
use App\Domains\Support\Events\SupportTicketCreated;
use App\Domains\Support\Events\SupportTicketDeleted;
use App\Domains\Support\Events\SupportTicketRestored;
use App\Domains\Support\Events\SupportTicketStatusChanged;
use App\Domains\Support\Events\SupportTicketUpdated;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Repositories\SupportTicketRepository;
use App\Domains\Support\Repositories\SupportTicketStatusHistoryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class SupportTicketService
{
    public function __construct(
        private readonly SupportTicketRepository $supportTicketRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly SupportTicketWorkflowService $workflowService,
        private readonly SupportTicketAssignmentService $assignmentService,
        private readonly SupportTicketStatusHistoryRepository $historyRepository,
        private readonly SupportSlaTrackingService $slaTrackingService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{tickets: \Illuminate\Contracts\Pagination\LengthAwarePaginator, statistics: array<string, mixed>}
     */
    public function list(array $filters = []): array
    {
        $filters = $this->workflowService->resolveFilters($filters);
        $companyId = isset($filters['company_id']) ? (int) $filters['company_id'] : null;

        return [
            'tickets' => $this->supportTicketRepository->paginateFiltered($filters),
            'statistics' => $this->supportTicketRepository->statistics($companyId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $companyIdentifier = null): array
    {
        $companyId = null;

        if (! blank($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail($companyIdentifier);
            $companyId = $company->id;
        }

        $filters = [
            'company_id' => $companyId,
            'per_page' => 8,
            'sort_by' => 'created_at',
            'sort_dir' => 'desc',
        ];

        $recentOpen = $this->supportTicketRepository->paginateFiltered(array_filter([
            ...$filters,
            'status' => SupportTicketStatus::Open->value,
        ], fn ($value) => $value !== null));

        $urgent = $this->supportTicketRepository->paginateFiltered(array_filter([
            ...$filters,
            'priorities' => [
                SupportTicketPriority::Critical->value,
                SupportTicketPriority::Emergency->value,
            ],
        ], fn ($value) => $value !== null));

        return [
            'statistics' => $this->supportTicketRepository->statistics($companyId),
            'recent_open' => $recentOpen,
            'urgent' => $urgent,
        ];
    }

    public function find(string $identifier, bool $withTrashed = false): SupportTicket
    {
        return $this->supportTicketRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): SupportTicket
    {
        $ticket = $this->find($identifier);

        $ticket->load([
            'company:id,uuid,company_name,email',
            'customer:id,uuid,first_name,last_name,company_name,email,customer_type,phone',
            'application:id,uuid,name,slug,platform,status',
            'department:id,uuid,name',
            'team:id,uuid,name,manager_id',
            'assignee:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'slaPolicy:id,uuid,name,code,response_target_minutes,resolution_target_minutes,at_risk_percent,business_hours_only',
            'privacyRequest:id,uuid,request_number,request_type,status',
        ]);

        return $ticket;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): SupportTicket
    {
        return DB::transaction(function () use ($data, $actor): SupportTicket {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $customer = $this->resolveCustomer($data['customer_id'] ?? null, $company->id);
            $application = $this->resolveApplication($data['application_id'] ?? null, $company->id);
            $assignee = $this->resolveAssignee($data['assigned_to'] ?? null);

            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['customer_id'] = $customer?->id;
            $payload['application_id'] = $application?->id;
            $payload['assigned_to'] = $assignee?->id;
            $payload['assignment_type'] = $assignee
                ? SupportTicketAssignmentType::Manual->value
                : null;
            $payload['assigned_at'] = $assignee ? now() : null;
            $payload['ticket_number'] = $this->supportTicketRepository->generateTicketNumber();
            $payload['status'] = $payload['status'] ?? SupportTicketStatus::Open->value;
            $payload['priority'] = $payload['priority'] ?? SupportTicketPriority::Medium->value;
            $payload['source'] = $payload['source'] ?? SupportTicketSource::Portal->value;
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['closed_at'] = $this->resolveClosedAt(
                SupportTicketStatus::tryFrom((string) $payload['status']),
                null
            );

            if (($payload['category'] ?? null) === 'emergency_support'
                && in_array($payload['priority'], [
                    SupportTicketPriority::Low->value,
                    SupportTicketPriority::Medium->value,
                    SupportTicketPriority::High->value,
                ], true)
            ) {
                $payload['priority'] = SupportTicketPriority::Emergency->value;
            }

            $ticket = $this->supportTicketRepository->createTicket($payload);
            $this->workflowService->recordCreated($ticket, $actor);
            $ticket = $this->slaTrackingService->initializeForTicket($ticket);
            event(new SupportTicketCreated($ticket, $actor));

            if ($ticket->assigned_to !== null) {
                event(new SupportTicketAssigned($ticket, $actor));
            }

            return $ticket;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): SupportTicket
    {
        return DB::transaction(function () use ($identifier, $data, $actor): SupportTicket {
            $ticket = $this->supportTicketRepository->findByIdentifierOrFail($identifier);
            $previousAssignee = $ticket->assigned_to;
            $previousStatus = $ticket->status instanceof SupportTicketStatus
                ? $ticket->status
                : SupportTicketStatus::tryFrom((string) $ticket->status);
            $previousPriority = $ticket->priority?->value ?? $ticket->priority;

            $payload = $this->preparePayload($data, isUpdate: true);

            if (array_key_exists('customer_id', $data)) {
                $customer = $this->resolveCustomer($data['customer_id'], $ticket->company_id);
                $payload['customer_id'] = $customer?->id;
            }

            if (array_key_exists('application_id', $data)) {
                $application = $this->resolveApplication($data['application_id'], $ticket->company_id);
                $payload['application_id'] = $application?->id;
            }

            if (array_key_exists('assigned_to', $data)) {
                $assignee = $this->resolveAssignee($data['assigned_to']);
                $payload['assigned_to'] = $assignee?->id;
                $payload['assignment_type'] = $assignee
                    ? SupportTicketAssignmentType::Manual->value
                    : null;
                $payload['assigned_at'] = $assignee ? now() : null;
            }

            $nextStatus = isset($payload['status'])
                ? SupportTicketStatus::tryFrom((string) $payload['status'])
                : $previousStatus;

            if ($nextStatus !== null && $previousStatus !== null && $nextStatus !== $previousStatus) {
                if (! $previousStatus->canTransitionTo($nextStatus)) {
                    throw new ApiException(
                        sprintf('Cannot transition from %s to %s.', $previousStatus->label(), $nextStatus->label()),
                        422
                    );
                }
            }

            if ($nextStatus !== null) {
                $payload['closed_at'] = $this->resolveClosedAt($nextStatus, $ticket->closed_at);
            }

            $payload['updated_by'] = $actor->id;

            $updated = $this->supportTicketRepository->updateTicket($ticket, $payload);
            event(new SupportTicketUpdated($updated, $actor));

            if ($nextStatus !== null && $previousStatus !== null && $nextStatus !== $previousStatus) {
                $this->historyRepository->recordForTicket(
                    $updated,
                    $nextStatus === SupportTicketStatus::Closed
                        ? SupportTicketWorkflowAction::Closed->value
                        : ($nextStatus === SupportTicketStatus::Reopened
                            ? SupportTicketWorkflowAction::Reopened->value
                            : SupportTicketWorkflowAction::StatusChanged->value),
                    $previousStatus->value,
                    $nextStatus->value,
                    $actor->id,
                    null,
                    ['via' => 'update']
                );
                event(new SupportTicketStatusChanged(
                    $updated,
                    $actor,
                    $previousStatus->value,
                    $nextStatus->value
                ));
            }

            if (($payload['priority'] ?? $previousPriority) !== $previousPriority) {
                $this->historyRepository->recordForTicket(
                    $updated,
                    SupportTicketWorkflowAction::PriorityChanged->value,
                    $previousStatus?->value,
                    $updated->status?->value ?? (string) $updated->status,
                    $actor->id,
                    null,
                    [
                        'from_priority' => $previousPriority,
                        'to_priority' => $updated->priority?->value ?? $updated->priority,
                    ]
                );
            }

            if (($payload['assigned_to'] ?? $previousAssignee) !== $previousAssignee
                && $updated->assigned_to !== null
            ) {
                event(new SupportTicketAssigned($updated, $actor));
            }

            if ($nextStatus === SupportTicketStatus::Closed
                && $previousStatus !== SupportTicketStatus::Closed
            ) {
                event(new SupportTicketClosed($updated, $actor));
            }

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(string $identifier, array $data, User $actor): SupportTicket
    {
        return $this->assignmentService->assign($identifier, $data, $actor);
    }

    public function close(string $identifier, User $actor, ?string $comments = null): SupportTicket
    {
        return $this->workflowService->close($identifier, $actor, $comments);
    }

    public function reopen(string $identifier, User $actor, ?string $comments = null): SupportTicket
    {
        return $this->workflowService->reopen($identifier, $actor, $comments);
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $ticket = $this->supportTicketRepository->findByIdentifierOrFail($identifier);
            $this->supportTicketRepository->updateTicket($ticket, ['updated_by' => $actor->id]);
            $ticket->delete();
            event(new SupportTicketDeleted($ticket, $actor));
        });
    }

    public function restore(string $identifier, User $actor): SupportTicket
    {
        return DB::transaction(function () use ($identifier, $actor): SupportTicket {
            $ticket = $this->supportTicketRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $ticket->trashed()) {
                throw new ApiException('Support ticket is not archived.', 422);
            }

            $ticket->restore();
            $restored = $this->supportTicketRepository->updateTicket($ticket, ['updated_by' => $actor->id]);
            event(new SupportTicketRestored($restored, $actor));

            return $restored;
        });
    }

    protected function resolveCustomer(mixed $identifier, int $companyId): ?Customer
    {
        if (blank($identifier)) {
            return null;
        }

        $customer = $this->customerRepository->findByIdentifierOrFail((string) $identifier);

        if ((int) $customer->company_id !== $companyId) {
            throw new ApiException('Customer does not belong to the selected company.', 422);
        }

        return $customer;
    }

    protected function resolveApplication(mixed $identifier, int $companyId): ?Application
    {
        if (blank($identifier)) {
            return null;
        }

        $application = $this->applicationRepository->findByIdentifierOrFail((string) $identifier);

        if ((int) $application->company_id !== $companyId) {
            throw new ApiException('Application does not belong to the selected company.', 422);
        }

        return $application;
    }

    protected function resolveAssignee(mixed $identifier): ?User
    {
        if (blank($identifier)) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit((string) $identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (! $user) {
            throw new ApiException('Assignee not found.', 422);
        }

        return $user;
    }

    protected function resolveClosedAt(?SupportTicketStatus $status, mixed $existingClosedAt): ?string
    {
        if ($status === null) {
            return $existingClosedAt instanceof \DateTimeInterface
                ? $existingClosedAt->format('Y-m-d H:i:s')
                : $existingClosedAt;
        }

        if ($status->isTerminal()) {
            return now()->toDateTimeString();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'subject',
            'description',
            'priority',
            'category',
            'status',
            'source',
            'involves_personal_data',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        if (array_key_exists('involves_personal_data', $payload)) {
            $payload['involves_personal_data'] = filter_var($payload['involves_personal_data'], FILTER_VALIDATE_BOOLEAN);
        }

        if (! $isUpdate && empty($payload['subject'])) {
            unset($payload['subject']);
        }

        return $payload;
    }
}
