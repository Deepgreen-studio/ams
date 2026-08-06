<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\RiskLevel;
use App\Domains\Compliance\Enums\RiskRegisterStatus;
use App\Domains\Compliance\Models\RiskRegister;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RiskRegisterRepository extends BaseRepository
{
    public function __construct(RiskRegister $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?RiskRegister
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var RiskRegister|null $risk */
        $risk = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('risk_number', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $risk;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): RiskRegister
    {
        $risk = $this->findByIdentifier($identifier, $withTrashed);

        if (! $risk) {
            abort(404, 'Risk register entry not found.');
        }

        return $risk;
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
                'dpiaAssessment:id,uuid,assessment_number,title',
                'owner:id,uuid,full_name,email',
                'creator:id,uuid,full_name,email',
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

        if (! empty($filters['dpia_assessment_id'])) {
            $query->where('dpia_assessment_id', (int) $filters['dpia_assessment_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('risk_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('mitigation_plan', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', (int) $filters['owner_id']);
        }

        if (($filters['mitigation_open'] ?? null) === true || ($filters['mitigation_open'] ?? null) === '1') {
            $query->whereIn('status', [
                RiskRegisterStatus::Identified->value,
                RiskRegisterStatus::Assessing->value,
                RiskRegisterStatus::Mitigating->value,
            ]);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id', 'risk_number', 'title', 'status', 'category',
            'risk_score', 'risk_level', 'review_due_at', 'created_at', 'updated_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRisk(array $data): RiskRegister
    {
        /** @var RiskRegister $risk */
        $risk = $this->model->newQuery()->create($data);

        return $risk->fresh([
            'company',
            'dpiaAssessment',
            'owner',
            'creator',
            'updater',
        ]) ?? $risk;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRisk(RiskRegister $risk, array $data): RiskRegister
    {
        $risk->fill($data);
        $risk->save();

        return $risk->refresh()->load([
            'company',
            'dpiaAssessment',
            'owner',
            'creator',
            'updater',
            'actions.performer',
        ]);
    }

    public function generateRiskNumber(): string
    {
        $prefix = 'RSK-'.now()->format('Ymd').'-';

        $last = $this->model->newQuery()
            ->withTrashed()
            ->where('risk_number', 'like', $prefix.'%')
            ->orderByDesc('risk_number')
            ->value('risk_number');

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

        $byLevel = $base->clone()
            ->selectRaw('risk_level, COUNT(*) as aggregate')
            ->groupBy('risk_level')
            ->pluck('aggregate', 'risk_level')
            ->all();

        $byCategory = $base->clone()
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->all();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereIn('status', RiskRegisterStatus::activeValues())->count(),
            'mitigating' => (clone $base)->where('status', RiskRegisterStatus::Mitigating->value)->count(),
            'monitoring' => (clone $base)->where('status', RiskRegisterStatus::Monitoring->value)->count(),
            'closed' => (clone $base)->where('status', RiskRegisterStatus::Closed->value)->count(),
            'critical' => (clone $base)->where('risk_level', RiskLevel::Critical->value)
                ->whereIn('status', RiskRegisterStatus::activeValues())
                ->count(),
            'high' => (clone $base)->where('risk_level', RiskLevel::High->value)
                ->whereIn('status', RiskRegisterStatus::activeValues())
                ->count(),
            'review_overdue' => (clone $base)
                ->whereNotNull('review_due_at')
                ->whereDate('review_due_at', '<', now()->toDateString())
                ->whereIn('status', RiskRegisterStatus::activeValues())
                ->count(),
            'by_status' => array_map('intval', $byStatus),
            'by_level' => array_map('intval', $byLevel),
            'by_category' => array_map('intval', $byCategory),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function riskMatrix(?int $companyId = null): array
    {
        $query = $this->model->newQuery()
            ->whereNotNull('likelihood')
            ->whereNotNull('impact')
            ->whereIn('status', RiskRegisterStatus::activeValues());

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $risks = $query->get([
            'id', 'uuid', 'risk_number', 'title', 'status', 'category',
            'likelihood', 'impact', 'risk_score', 'risk_level', 'company_id',
        ]);

        $matrix = [];
        for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
            for ($impact = 1; $impact <= 5; $impact++) {
                $matrix["{$likelihood}-{$impact}"] = [
                    'likelihood' => $likelihood,
                    'impact' => $impact,
                    'score' => $likelihood * $impact,
                    'level' => RiskLevel::fromRiskScore($likelihood * $impact)->value,
                    'count' => 0,
                    'risks' => [],
                ];
            }
        }

        foreach ($risks as $risk) {
            $key = "{$risk->likelihood}-{$risk->impact}";
            if (! isset($matrix[$key])) {
                continue;
            }
            $matrix[$key]['count']++;
            $matrix[$key]['risks'][] = [
                'uuid' => $risk->uuid,
                'risk_number' => $risk->risk_number,
                'title' => $risk->title,
                'risk_level' => $risk->risk_level?->value ?? $risk->risk_level,
                'risk_score' => $risk->risk_score,
            ];
        }

        return [
            'cells' => array_values($matrix),
            'risks' => $risks,
        ];
    }

    /**
     * @return Collection<int, RiskRegister>
     */
    public function mitigationQueue(?int $companyId = null, int $limit = 10): Collection
    {
        $query = $this->model->newQuery()
            ->with(['owner:id,uuid,full_name,email', 'company:id,uuid,company_name'])
            ->whereIn('status', [
                RiskRegisterStatus::Identified->value,
                RiskRegisterStatus::Assessing->value,
                RiskRegisterStatus::Mitigating->value,
            ])
            ->orderByDesc('risk_score')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }
}
