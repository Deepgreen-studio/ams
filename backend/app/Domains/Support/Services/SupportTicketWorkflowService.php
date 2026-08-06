<?php

namespace App\Domains\Support\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Companies\Repositories\DepartmentRepository;
use App\Domains\Companies\Repositories\TeamRepository;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Enums\SupportTicketWorkflowAction;
use App\Domains\Support\Events\SupportTicketClosed;
use App\Domains\Support\Events\SupportTicketReopened;
use App\Domains\Support\Events\SupportTicketStatusChanged;
use App\Domains\Support\Events\SupportTicketUpdated;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Models\SupportTicketStatusHistory;
use App\Domains\Support\Repositories\SupportTicketRepository;
use App\Domains\Support\Repositories\SupportTicketStatusHistoryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SupportTicketWorkflowService
{
    public function __construct(
        private readonly SupportTicketRepository $supportTicketRepository,
        private readonly SupportTicketStatusHistoryRepository $historyRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly TeamRepository $teamRepository,
        private readonly SupportSlaTrackingService $slaTrackingService,
    ) {}

    /**
     * @return Collection<int, SupportTicketStatusHistory>
     */
    public function timeline(string $identifier): Collection
    {
        $ticket = $this->supportTicketRepository->findByIdentifierOrFail($identifier);

        return $this->historyRepository->forTicket($ticket->id);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: array<int, array<string, mixed>>, statistics: array<string, mixed>}
     */
    public function kanban(array $filters = []): array
    {
        $filters = $this->resolveFilters($filters);
        $companyId = isset($filters['company_id']) ? (int) $filters['company_id'] : null;
        $perColumn = max(5, min((int) ($filters['per_column'] ?? 20), 50));

        $tickets = $this->supportTicketRepository->listForBoard($filters, $perColumn);
        $grouped = $tickets->groupBy(fn (SupportTicket $ticket) => $ticket->status?->value ?? (string) $ticket->status);

        $columns = [];
        foreach (SupportTicketStatus::kanbanColumns() as $status) {
            $items = ($grouped[$status->value] ?? collect())->take($perColumn)->values();
            $columns[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $items->count(),
                'tickets' => $items,
            ];
        }

        return [
            'columns' => $columns,
            'statistics' => $this->supportTicketRepository->statistics($companyId),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{tickets: \Illuminate\Contracts\Pagination\LengthAwarePaginator, statistics: array<string, mixed>}
     */
    public function queue(array $filters = []): array
    {
        $filters = $this->resolveFilters($filters);
        $queue = (string) ($filters['queue'] ?? 'open');

        $filters = match ($queue) {
            'unassigned', 'assignment' => array_merge($filters, [
                'needs_assignment' => true,
                'sort_by' => $filters['sort_by'] ?? 'priority',
                'sort_dir' => $filters['sort_dir'] ?? 'desc',
            ]),
            'mine' => array_merge($filters, [
                'statuses' => $filters['statuses'] ?? [
                    SupportTicketStatus::Open->value,
                    SupportTicketStatus::Pending->value,
                    SupportTicketStatus::InProgress->value,
                    SupportTicketStatus::WaitingForCustomer->value,
                    SupportTicketStatus::Reopened->value,
                ],
            ]),
            'critical' => array_merge($filters, [
                'priorities' => [
                    SupportTicketPriority::Critical->value,
                    SupportTicketPriority::Emergency->value,
                ],
                'statuses' => $filters['statuses'] ?? [
                    SupportTicketStatus::Open->value,
                    SupportTicketStatus::Pending->value,
                    SupportTicketStatus::InProgress->value,
                    SupportTicketStatus::WaitingForCustomer->value,
                    SupportTicketStatus::Reopened->value,
                ],
                'sort_by' => $filters['sort_by'] ?? 'priority',
            ]),
            'waiting' => array_merge($filters, [
                'status' => SupportTicketStatus::WaitingForCustomer->value,
            ]),
            'reopened' => array_merge($filters, [
                'status' => SupportTicketStatus::Reopened->value,
            ]),
            default => array_merge($filters, [
                'statuses' => $filters['statuses'] ?? [
                    SupportTicketStatus::Open->value,
                    SupportTicketStatus::Pending->value,
                    SupportTicketStatus::InProgress->value,
                    SupportTicketStatus::WaitingForCustomer->value,
                    SupportTicketStatus::Reopened->value,
                ],
                'sort_by' => $filters['sort_by'] ?? 'priority',
            ]),
        };

        unset($filters['queue']);

        $companyId = isset($filters['company_id']) ? (int) $filters['company_id'] : null;

        return [
            'tickets' => $this->supportTicketRepository->paginateFiltered($filters),
            'statistics' => $this->supportTicketRepository->statistics($companyId),
        ];
    }

    public function transition(
        string $identifier,
        string $status,
        User $actor,
        ?string $comments = null
    ): SupportTicket {
        return DB::transaction(function () use ($identifier, $status, $actor, $comments): SupportTicket {
            $ticket = $this->supportTicketRepository->findByIdentifierOrFail($identifier);
            $current = $ticket->status instanceof SupportTicketStatus
                ? $ticket->status
                : SupportTicketStatus::tryFrom((string) $ticket->status);

            $target = SupportTicketStatus::tryFrom($status);
            if (! $current || ! $target) {
                throw new ApiException('Invalid ticket status.', 422);
            }

            if ($current === $target) {
                return $ticket->load([
                    'company', 'customer', 'application', 'department', 'team', 'assignee', 'creator', 'updater',
                ]);
            }

            if (! $current->canTransitionTo($target)) {
                throw new ApiException(
                    sprintf('Cannot transition from %s to %s.', $current->label(), $target->label()),
                    422
                );
            }

            $payload = [
                'status' => $target->value,
                'updated_by' => $actor->id,
                'closed_at' => $target->isTerminal() ? now() : null,
            ];

            if ($target === SupportTicketStatus::Reopened) {
                $payload['closed_at'] = null;
            }

            $updated = $this->supportTicketRepository->updateTicket($ticket, $payload);

            $action = match ($target) {
                SupportTicketStatus::Reopened => SupportTicketWorkflowAction::Reopened,
                SupportTicketStatus::Closed => SupportTicketWorkflowAction::Closed,
                default => SupportTicketWorkflowAction::StatusChanged,
            };

            $this->historyRepository->recordForTicket(
                $updated,
                $action->value,
                $current->value,
                $target->value,
                $actor->id,
                $comments,
                ['transition' => true]
            );

            event(new SupportTicketUpdated($updated, $actor));
            event(new SupportTicketStatusChanged($updated, $actor, $current->value, $target->value, $comments));

            if ($target === SupportTicketStatus::Closed) {
                event(new SupportTicketClosed($updated, $actor));
            }

            if ($target === SupportTicketStatus::Reopened) {
                event(new SupportTicketReopened($updated, $actor));
            }

            $updated = $this->slaTrackingService->handleStatusChange($updated, $current, $target, $actor);

            return $updated->load([
                'company', 'customer', 'application', 'department', 'team', 'assignee', 'creator', 'updater', 'slaPolicy',
            ]);
        });
    }

    public function reopen(string $identifier, User $actor, ?string $comments = null): SupportTicket
    {
        return $this->transition($identifier, SupportTicketStatus::Reopened->value, $actor, $comments);
    }

    public function close(string $identifier, User $actor, ?string $comments = null): SupportTicket
    {
        return $this->transition($identifier, SupportTicketStatus::Closed->value, $actor, $comments);
    }

    public function recordCreated(SupportTicket $ticket, User $actor): void
    {
        $this->historyRepository->recordForTicket(
            $ticket,
            SupportTicketWorkflowAction::Created->value,
            null,
            $ticket->status?->value ?? (string) $ticket->status,
            $actor->id,
            null,
            [
                'priority' => $ticket->priority?->value ?? $ticket->priority,
                'assignment_type' => $ticket->assignment_type?->value ?? $ticket->assignment_type,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function resolveFilters(array $filters): array
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;

        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier);
            $filters['company_id'] = $company->id;
        }

        $departmentIdentifier = $filters['department'] ?? $filters['department_id'] ?? null;
        if (! empty($departmentIdentifier) && ! is_numeric($departmentIdentifier)) {
            $department = $this->departmentRepository->findByIdentifierOrFail((string) $departmentIdentifier);
            $filters['department_id'] = $department->id;
        }

        $teamIdentifier = $filters['team'] ?? $filters['team_id'] ?? null;
        if (! empty($teamIdentifier) && ! is_numeric($teamIdentifier)) {
            $team = $this->teamRepository->findByIdentifierOrFail((string) $teamIdentifier);
            $filters['team_id'] = $team->id;
        }

        $assigneeIdentifier = $filters['assignee'] ?? $filters['assigned_to'] ?? null;
        if (! empty($assigneeIdentifier) && ! is_numeric($assigneeIdentifier)) {
            $assignee = User::query()->where('uuid', $assigneeIdentifier)->firstOrFail();
            $filters['assigned_to'] = $assignee->id;
        }

        return $filters;
    }
}
