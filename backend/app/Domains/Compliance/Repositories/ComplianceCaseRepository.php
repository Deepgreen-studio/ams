<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\ComplianceCasePriority;
use App\Domains\Compliance\Enums\ComplianceCaseStatus;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ComplianceCaseRepository extends BaseRepository
{
    public function __construct(ComplianceCase $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?ComplianceCase
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ComplianceCase|null $case */
        $case = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('case_number', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $case;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): ComplianceCase
    {
        $case = $this->findByIdentifier($identifier, $withTrashed);

        if (! $case) {
            abort(404, 'Compliance case not found.');
        }

        return $case;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'company:id,uuid,company_name',
                'assignee:id,uuid,full_name,email',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
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

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('case_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['statuses']) && is_array($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        if (! empty($filters['case_type'])) {
            $query->where('case_type', $filters['case_type']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['priorities']) && is_array($filters['priorities'])) {
            $query->whereIn('priority', $filters['priorities']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (! empty($filters['due_before'])) {
            $query->whereDate('due_date', '<=', $filters['due_before']);
        }

        if (! empty($filters['due_after'])) {
            $query->whereDate('due_date', '>=', $filters['due_after']);
        }

        if (($filters['overdue'] ?? null) === true || ($filters['overdue'] ?? null) === '1') {
            $query->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('status', ComplianceCaseStatus::activeValues());
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'case_number',
            'title',
            'case_type',
            'priority',
            'status',
            'due_date',
            'completed_at',
            'created_at',
            'updated_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCase(array $data): ComplianceCase
    {
        /** @var ComplianceCase $case */
        $case = $this->model->newQuery()->create($data);

        return $case->fresh(['company', 'assignee', 'creator', 'updater']) ?? $case;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCase(ComplianceCase $case, array $data): ComplianceCase
    {
        $case->fill($data);
        $case->save();

        return $case->refresh()->load(['company', 'assignee', 'creator', 'updater']);
    }

    public function generateCaseNumber(): string
    {
        $prefix = 'CMP-'.now()->format('Ymd').'-';

        $last = $this->model->newQuery()
            ->withTrashed()
            ->where('case_number', 'like', $prefix.'%')
            ->orderByDesc('case_number')
            ->value('case_number');

        $sequence = 1;

        if (is_string($last) && preg_match('/(\d{5})$/', $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
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

        $byStatus = $base->clone()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $byType = $base->clone()
            ->selectRaw('case_type, COUNT(*) as aggregate')
            ->groupBy('case_type')
            ->pluck('aggregate', 'case_type')
            ->all();

        $byPriority = $base->clone()
            ->selectRaw('priority, COUNT(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority')
            ->all();

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where('status', ComplianceCaseStatus::Open->value)->count(),
            'in_progress' => (clone $base)->where('status', ComplianceCaseStatus::InProgress->value)->count(),
            'under_review' => (clone $base)->where('status', ComplianceCaseStatus::UnderReview->value)->count(),
            'pending' => (clone $base)->where('status', ComplianceCaseStatus::Pending->value)->count(),
            'completed' => (clone $base)->where('status', ComplianceCaseStatus::Completed->value)->count(),
            'closed' => (clone $base)->where('status', ComplianceCaseStatus::Closed->value)->count(),
            'cancelled' => (clone $base)->where('status', ComplianceCaseStatus::Cancelled->value)->count(),
            'active' => (clone $base)->whereIn('status', ComplianceCaseStatus::activeValues())->count(),
            'overdue' => (clone $base)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('status', ComplianceCaseStatus::activeValues())
                ->count(),
            'critical' => (clone $base)
                ->where('priority', ComplianceCasePriority::Critical->value)
                ->whereIn('status', ComplianceCaseStatus::activeValues())
                ->count(),
            'high_or_critical' => (clone $base)
                ->whereIn('priority', ComplianceCasePriority::elevatedValues())
                ->whereIn('status', ComplianceCaseStatus::activeValues())
                ->count(),
            'unassigned' => (clone $base)
                ->whereNull('assigned_to')
                ->whereIn('status', ComplianceCaseStatus::activeValues())
                ->count(),
            'trashed' => (clone $base)->onlyTrashed()->count(),
            'by_status' => array_map('intval', $byStatus),
            'by_type' => array_map('intval', $byType),
            'by_priority' => array_map('intval', $byPriority),
        ];
    }

    /**
     * @return Collection<int, ComplianceCase>
     */
    public function recentActive(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email'])
            ->whereIn('status', ComplianceCaseStatus::activeValues())
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, ComplianceCase>
     */
    public function elevatedPriority(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email'])
            ->whereIn('priority', ComplianceCasePriority::elevatedValues())
            ->whereIn('status', ComplianceCaseStatus::activeValues())
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }
}
