<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\DataBreachSeverity;
use App\Domains\Compliance\Enums\DataBreachStatus;
use App\Domains\Compliance\Models\DataBreach;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DataBreachRepository extends BaseRepository
{
    public function __construct(DataBreach $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?DataBreach
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var DataBreach|null $breach */
        $breach = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('breach_number', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $breach;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): DataBreach
    {
        $breach = $this->findByIdentifier($identifier, $withTrashed);

        if (! $breach) {
            abort(404, 'Data breach not found.');
        }

        return $breach;
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
                $builder->where('breach_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('regulator_reference', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['breach_type'])) {
            $query->where('breach_type', $filters['breach_type']);
        }

        if (! empty($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (($filters['regulator_overdue'] ?? null) === true || ($filters['regulator_overdue'] ?? null) === '1') {
            $query->where('regulator_notification_required', true)
                ->whereNull('regulator_notified_at')
                ->whereNotNull('regulator_deadline_at')
                ->where('regulator_deadline_at', '<', now())
                ->whereIn('status', DataBreachStatus::activeValues());
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'breach_number',
            'title',
            'status',
            'severity',
            'breach_type',
            'discovered_at',
            'risk_score',
            'affected_user_count',
            'regulator_deadline_at',
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
    public function createBreach(array $data): DataBreach
    {
        /** @var DataBreach $breach */
        $breach = $this->model->newQuery()->create($data);

        return $breach->fresh([
            'company',
            'assignee',
            'creator',
            'updater',
        ]) ?? $breach;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBreach(DataBreach $breach, array $data): DataBreach
    {
        $breach->fill($data);
        $breach->save();

        return $breach->refresh()->load([
            'company',
            'assignee',
            'riskAssessor',
            'creator',
            'updater',
            'actions.performer',
            'notifications.sender',
        ]);
    }

    public function generateBreachNumber(): string
    {
        $prefix = 'BRH-'.now()->format('Ymd').'-';

        $last = $this->model->newQuery()
            ->withTrashed()
            ->where('breach_number', 'like', $prefix.'%')
            ->orderByDesc('breach_number')
            ->value('breach_number');

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

        $bySeverity = $base->clone()
            ->selectRaw('severity, COUNT(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity')
            ->all();

        $byType = $base->clone()
            ->selectRaw('breach_type, COUNT(*) as aggregate')
            ->groupBy('breach_type')
            ->pluck('aggregate', 'breach_type')
            ->all();

        return [
            'total' => (clone $base)->count(),
            'reported' => (clone $base)->where('status', DataBreachStatus::Reported->value)->count(),
            'assessing' => (clone $base)->where('status', DataBreachStatus::Assessing->value)->count(),
            'contained' => (clone $base)->where('status', DataBreachStatus::Contained->value)->count(),
            'recovering' => (clone $base)->where('status', DataBreachStatus::Recovering->value)->count(),
            'notifying' => (clone $base)->where('status', DataBreachStatus::Notifying->value)->count(),
            'closed' => (clone $base)->where('status', DataBreachStatus::Closed->value)->count(),
            'cancelled' => (clone $base)->where('status', DataBreachStatus::Cancelled->value)->count(),
            'active' => (clone $base)->whereIn('status', DataBreachStatus::activeValues())->count(),
            'critical' => (clone $base)->where('severity', DataBreachSeverity::Critical->value)
                ->whereIn('status', DataBreachStatus::activeValues())
                ->count(),
            'regulator_pending' => (clone $base)
                ->where('regulator_notification_required', true)
                ->whereNull('regulator_notified_at')
                ->whereIn('status', DataBreachStatus::activeValues())
                ->count(),
            'regulator_overdue' => (clone $base)
                ->where('regulator_notification_required', true)
                ->whereNull('regulator_notified_at')
                ->whereNotNull('regulator_deadline_at')
                ->where('regulator_deadline_at', '<', now())
                ->whereIn('status', DataBreachStatus::activeValues())
                ->count(),
            'customer_pending' => (clone $base)
                ->where('customer_notification_required', true)
                ->whereNull('customer_notified_at')
                ->whereIn('status', DataBreachStatus::activeValues())
                ->count(),
            'unassigned' => (clone $base)
                ->whereNull('assigned_to')
                ->whereIn('status', DataBreachStatus::activeValues())
                ->count(),
            'affected_users_total' => (int) (clone $base)->sum('affected_user_count'),
            'by_status' => array_map('intval', $byStatus),
            'by_severity' => array_map('intval', $bySeverity),
            'by_type' => array_map('intval', $byType),
        ];
    }

    /**
     * @return Collection<int, DataBreach>
     */
    public function recentActive(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email'])
            ->whereIn('status', DataBreachStatus::activeValues())
            ->orderByDesc('discovered_at')
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, DataBreach>
     */
    public function regulatorQueue(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email'])
            ->where('regulator_notification_required', true)
            ->whereNull('regulator_notified_at')
            ->whereIn('status', DataBreachStatus::activeValues())
            ->orderBy('regulator_deadline_at')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function riskMatrix(?int $companyId = null): array
    {
        $query = $this->model->newQuery()
            ->whereNotNull('risk_likelihood')
            ->whereNotNull('risk_impact')
            ->whereIn('status', DataBreachStatus::activeValues());

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $breaches = $query
            ->with(['company:id,uuid,company_name'])
            ->get(['id', 'uuid', 'breach_number', 'title', 'status', 'severity', 'risk_likelihood', 'risk_impact', 'risk_score', 'risk_level', 'company_id', 'affected_user_count']);

        $matrix = [];
        for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
            for ($impact = 1; $impact <= 5; $impact++) {
                $matrix["{$likelihood}-{$impact}"] = [
                    'likelihood' => $likelihood,
                    'impact' => $impact,
                    'score' => $likelihood * $impact,
                    'level' => DataBreachSeverity::fromRiskScore($likelihood * $impact)->value,
                    'count' => 0,
                    'breaches' => [],
                ];
            }
        }

        foreach ($breaches as $breach) {
            $key = "{$breach->risk_likelihood}-{$breach->risk_impact}";
            if (! isset($matrix[$key])) {
                continue;
            }
            $matrix[$key]['count']++;
            $matrix[$key]['breaches'][] = [
                'uuid' => $breach->uuid,
                'breach_number' => $breach->breach_number,
                'title' => $breach->title,
                'severity' => $breach->severity?->value ?? $breach->severity,
                'risk_score' => $breach->risk_score,
                'affected_user_count' => $breach->affected_user_count,
            ];
        }

        return [
            'cells' => array_values($matrix),
            'breaches' => $breaches,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reportSummary(?int $companyId = null): array
    {
        $stats = $this->statistics($companyId);
        $matrix = $this->riskMatrix($companyId);

        return [
            'statistics' => $stats,
            'risk_matrix' => $matrix,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
