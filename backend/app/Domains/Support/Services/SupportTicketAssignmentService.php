<?php

namespace App\Domains\Support\Services;

use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Models\Team;
use App\Domains\Companies\Repositories\DepartmentRepository;
use App\Domains\Companies\Repositories\TeamRepository;
use App\Domains\Support\Enums\SupportTicketAssignmentType;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Enums\SupportTicketWorkflowAction;
use App\Domains\Support\Events\SupportTicketAssigned;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Repositories\SupportTicketRepository;
use App\Domains\Support\Repositories\SupportTicketStatusHistoryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupportTicketAssignmentService
{
    public function __construct(
        private readonly SupportTicketRepository $supportTicketRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly TeamRepository $teamRepository,
        private readonly SupportTicketStatusHistoryRepository $historyRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(string $identifier, array $data, User $actor): SupportTicket
    {
        return DB::transaction(function () use ($identifier, $data, $actor): SupportTicket {
            $ticket = $this->supportTicketRepository->findByIdentifierOrFail($identifier);
            $type = SupportTicketAssignmentType::tryFrom((string) ($data['type'] ?? SupportTicketAssignmentType::Manual->value));

            if (! $type) {
                throw new ApiException('Invalid assignment type.', 422);
            }

            $fromStatus = $ticket->status?->value ?? (string) $ticket->status;
            $comments = isset($data['comments']) ? (string) $data['comments'] : null;

            $payload = match ($type) {
                SupportTicketAssignmentType::Auto => $this->buildAutoAssignment($ticket),
                SupportTicketAssignmentType::Department => $this->buildDepartmentAssignment($ticket, $data),
                SupportTicketAssignmentType::Team => $this->buildTeamAssignment($ticket, $data),
                SupportTicketAssignmentType::Agent,
                SupportTicketAssignmentType::Manual => $this->buildAgentAssignment($ticket, $data, $type),
            };

            $payload['updated_by'] = $actor->id;
            $payload['assigned_at'] = now();

            $currentStatus = $ticket->status instanceof SupportTicketStatus
                ? $ticket->status
                : SupportTicketStatus::tryFrom((string) $ticket->status);

            if (($payload['assigned_to'] ?? null)
                && in_array($currentStatus, [
                    SupportTicketStatus::Open,
                    SupportTicketStatus::Pending,
                    SupportTicketStatus::Reopened,
                    null,
                ], true)
            ) {
                $payload['status'] = SupportTicketStatus::InProgress->value;
            }

            $updated = $this->supportTicketRepository->updateTicket($ticket, $payload);

            $this->historyRepository->recordForTicket(
                $updated,
                SupportTicketWorkflowAction::Assigned->value,
                $fromStatus,
                $updated->status?->value ?? (string) $updated->status,
                $actor->id,
                $comments,
                [
                    'assignment_type' => $type->value,
                    'assigned_to' => $updated->assigned_to,
                    'department_id' => $updated->department_id,
                    'team_id' => $updated->team_id,
                ]
            );

            event(new SupportTicketAssigned($updated, $actor));

            return $updated;
        });
    }

    /**
     * @return Collection<int, User>
     */
    public function availableAgents(): Collection
    {
        return User::query()
            ->role(['support-agent', 'support-manager'])
            ->orderBy('id')
            ->get(['id', 'uuid', 'full_name', 'email']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAutoAssignment(SupportTicket $ticket): array
    {
        $agent = $this->pickRoundRobinAgent($ticket->company_id);

        if (! $agent) {
            throw new ApiException('No support agents available for auto assignment.', 422);
        }

        return [
            'assignment_type' => SupportTicketAssignmentType::Auto->value,
            'assigned_to' => $agent->id,
            'department_id' => $ticket->department_id,
            'team_id' => $ticket->team_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildDepartmentAssignment(SupportTicket $ticket, array $data): array
    {
        if (blank($data['department_id'] ?? null)) {
            throw new ApiException('Department is required for department assignment.', 422);
        }

        $department = $this->resolveDepartment((string) $data['department_id'], $ticket->company_id);

        return [
            'assignment_type' => SupportTicketAssignmentType::Department->value,
            'department_id' => $department->id,
            'team_id' => null,
            'assigned_to' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildTeamAssignment(SupportTicket $ticket, array $data): array
    {
        if (blank($data['team_id'] ?? null)) {
            throw new ApiException('Team is required for team assignment.', 422);
        }

        $team = $this->resolveTeam((string) $data['team_id'], $ticket->company_id);

        return [
            'assignment_type' => SupportTicketAssignmentType::Team->value,
            'team_id' => $team->id,
            'department_id' => $team->department_id,
            'assigned_to' => $team->manager_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildAgentAssignment(
        SupportTicket $ticket,
        array $data,
        SupportTicketAssignmentType $type
    ): array {
        if (blank($data['assigned_to'] ?? null)) {
            throw new ApiException('Agent is required for '.$type->label().'.', 422);
        }

        $agent = $this->resolveUser((string) $data['assigned_to']);
        $departmentId = $ticket->department_id;
        $teamId = $ticket->team_id;

        if (! blank($data['department_id'] ?? null)) {
            $departmentId = $this->resolveDepartment((string) $data['department_id'], $ticket->company_id)->id;
        }

        if (! blank($data['team_id'] ?? null)) {
            $team = $this->resolveTeam((string) $data['team_id'], $ticket->company_id);
            $teamId = $team->id;
            $departmentId = $team->department_id ?? $departmentId;
        }

        return [
            'assignment_type' => $type->value,
            'assigned_to' => $agent->id,
            'department_id' => $departmentId,
            'team_id' => $teamId,
        ];
    }

    protected function pickRoundRobinAgent(int $companyId): ?User
    {
        $agents = $this->availableAgents();
        if ($agents->isEmpty()) {
            return null;
        }

        $lastAssignedId = $this->supportTicketRepository->lastAutoAssignedUserId($companyId);
        if ($lastAssignedId === null) {
            return $agents->first();
        }

        $ids = $agents->pluck('id')->values();
        $index = $ids->search($lastAssignedId);

        if ($index === false) {
            return $agents->first();
        }

        $nextIndex = ($index + 1) % $ids->count();

        return $agents->get($nextIndex);
    }

    protected function resolveDepartment(string $identifier, int $companyId): Department
    {
        $department = $this->departmentRepository->findByIdentifierOrFail($identifier);

        if ((int) $department->company_id !== $companyId) {
            throw new ApiException('Department does not belong to the ticket company.', 422);
        }

        return $department;
    }

    protected function resolveTeam(string $identifier, int $companyId): Team
    {
        $team = $this->teamRepository->findByIdentifierOrFail($identifier);

        if ((int) $team->company_id !== $companyId) {
            throw new ApiException('Team does not belong to the ticket company.', 422);
        }

        return $team;
    }

    protected function resolveUser(string $identifier): User
    {
        /** @var User|null $user */
        $user = User::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (! $user) {
            throw new ApiException('Assignee not found.', 422);
        }

        return $user;
    }
}
