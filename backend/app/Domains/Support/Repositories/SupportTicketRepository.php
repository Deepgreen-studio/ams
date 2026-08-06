<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SupportTicketRepository extends BaseRepository
{
    public function __construct(SupportTicket $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?SupportTicket
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var SupportTicket|null $ticket */
        $ticket = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
            $builder->orWhere('ticket_number', $identifier);
        })->first();

        return $ticket;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): SupportTicket
    {
        $ticket = $this->findByIdentifier($identifier, $withTrashed);

        if (! $ticket) {
            abort(404, 'Support ticket not found.');
        }

        return $ticket;
    }

    /**
     * @return list<string>
     */
    protected function defaultRelations(): array
    {
        return [
            'company:id,uuid,company_name',
            'customer:id,uuid,first_name,last_name,company_name,email,customer_type',
            'application:id,uuid,name,slug,platform',
            'department:id,uuid,name',
            'team:id,uuid,name,manager_id',
            'assignee:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with($this->defaultRelations())
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, SupportTicket>
     */
    public function listForBoard(array $filters = [], int $perColumn = 25): Collection
    {
        $statuses = array_map(
            fn (SupportTicketStatus $status) => $status->value,
            SupportTicketStatus::kanbanColumns()
        );

        return $this->filteredQuery($filters)
            ->with($this->defaultRelations())
            ->whereIn('status', $statuses)
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 1 WHEN 'critical' THEN 2 WHEN 'high' THEN 3 WHEN 'medium' THEN 4 WHEN 'low' THEN 5 ELSE 6 END")
            ->orderByDesc('updated_at')
            ->limit(max(1, $perColumn) * count($statuses))
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (! empty($filters['application_id'])) {
            $query->where('application_id', (int) $filters['application_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (! empty($filters['team_id'])) {
            $query->where('team_id', (int) $filters['team_id']);
        }

        if (! empty($filters['statuses']) && is_array($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        } elseif (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priorities']) && is_array($filters['priorities'])) {
            $query->whereIn('priority', $filters['priorities']);
        } elseif (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['assignment_type'])) {
            $query->where('assignment_type', $filters['assignment_type']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (($filters['unassigned'] ?? null) === true || ($filters['unassigned'] ?? null) === '1') {
            $query->whereNull('assigned_to')
                ->whereNull('team_id')
                ->whereNull('department_id');
        }

        if (($filters['needs_assignment'] ?? null) === true || ($filters['needs_assignment'] ?? null) === '1') {
            $query->where(function (Builder $builder): void {
                $builder->whereNull('assigned_to')
                    ->orWhereNull('assignment_type');
            })->whereNotIn('status', [
                SupportTicketStatus::Closed->value,
                SupportTicketStatus::Cancelled->value,
            ]);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'ticket_number',
            'subject',
            'priority',
            'category',
            'status',
            'source',
            'assigned_at',
            'created_at',
            'updated_at',
            'closed_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        if ($sortBy === 'priority') {
            $priorityOrder = "CASE priority WHEN 'emergency' THEN 1 WHEN 'critical' THEN 2 WHEN 'high' THEN 3 WHEN 'medium' THEN 4 WHEN 'low' THEN 5 ELSE 6 END";

            return $query->orderByRaw($priorityOrder.' '.($sortDir === 'asc' ? 'DESC' : 'ASC'))
                ->orderByDesc('created_at');
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return array<string, mixed>
     */
    public function statistics(?int $companyId = null): array
    {
        $base = $this->model->newQuery();

        if ($companyId !== null) {
            $base->where('company_id', $companyId);
        }

        $byStatus = (clone $base)
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $byPriority = (clone $base)
            ->select('priority', DB::raw('count(*) as aggregate'))
            ->groupBy('priority')
            ->pluck('aggregate', 'priority')
            ->all();

        $byCategory = (clone $base)
            ->select('category', DB::raw('count(*) as aggregate'))
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->all();

        $activeStatusesExcluded = [
            SupportTicketStatus::Closed->value,
            SupportTicketStatus::Cancelled->value,
        ];

        return [
            'total' => (clone $base)->count(),
            'open' => (int) ($byStatus[SupportTicketStatus::Open->value] ?? 0),
            'pending' => (int) ($byStatus[SupportTicketStatus::Pending->value] ?? 0),
            'in_progress' => (int) ($byStatus[SupportTicketStatus::InProgress->value] ?? 0),
            'waiting_for_customer' => (int) ($byStatus[SupportTicketStatus::WaitingForCustomer->value] ?? 0),
            'resolved' => (int) ($byStatus[SupportTicketStatus::Resolved->value] ?? 0),
            'closed' => (int) ($byStatus[SupportTicketStatus::Closed->value] ?? 0),
            'reopened' => (int) ($byStatus[SupportTicketStatus::Reopened->value] ?? 0),
            'cancelled' => (int) ($byStatus[SupportTicketStatus::Cancelled->value] ?? 0),
            'unassigned' => (clone $base)->whereNull('assigned_to')->whereNotIn('status', $activeStatusesExcluded)->count(),
            'needs_assignment' => (clone $base)->where(function (Builder $builder): void {
                $builder->whereNull('assigned_to')
                    ->whereNull('department_id')
                    ->whereNull('team_id');
            })->whereNotIn('status', $activeStatusesExcluded)->count(),
            'urgent_or_critical' => (clone $base)->whereIn('priority', [
                SupportTicketPriority::Critical->value,
                SupportTicketPriority::Emergency->value,
            ])->whereNotIn('status', $activeStatusesExcluded)->count(),
            'critical_or_emergency' => (clone $base)->whereIn('priority', [
                SupportTicketPriority::Critical->value,
                SupportTicketPriority::Emergency->value,
            ])->whereNotIn('status', $activeStatusesExcluded)->count(),
            'trashed' => (clone $base)->onlyTrashed()->count(),
            'by_status' => array_map('intval', $byStatus),
            'by_priority' => array_map('intval', $byPriority),
            'by_category' => array_map('intval', $byCategory),
        ];
    }

    public function generateTicketNumber(): string
    {
        $prefix = 'SUP-'.now()->format('Ymd').'-';

        $last = $this->model->newQuery()
            ->withTrashed()
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        $sequence = 1;

        if (is_string($last) && preg_match('/(\d{5})$/', $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function lastAutoAssignedUserId(?int $companyId = null): ?int
    {
        $query = $this->model->newQuery()
            ->where('assignment_type', 'auto')
            ->whereNotNull('assigned_to')
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $value = $query->value('assigned_to');

        return $value !== null ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTicket(array $data): SupportTicket
    {
        /** @var SupportTicket $ticket */
        $ticket = $this->model->newQuery()->create($data);

        return $ticket->fresh($this->defaultRelations()) ?? $ticket;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTicket(SupportTicket $ticket, array $data): SupportTicket
    {
        $ticket->fill($data);
        $ticket->save();

        return $ticket->refresh()->load($this->defaultRelations());
    }
}
