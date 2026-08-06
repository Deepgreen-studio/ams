<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\DpiaStatus;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DpiaAssessmentRepository extends BaseRepository
{
    public function __construct(DpiaAssessment $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?DpiaAssessment
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var DpiaAssessment|null $assessment */
        $assessment = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('assessment_number', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $assessment;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): DpiaAssessment
    {
        $assessment = $this->findByIdentifier($identifier, $withTrashed);

        if (! $assessment) {
            abort(404, 'DPIA assessment not found.');
        }

        return $assessment;
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
                'approver:id,uuid,full_name,email',
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
                $builder->where('assessment_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('processing_purpose', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['template_code'])) {
            $query->where('template_code', $filters['template_code']);
        }

        if (! empty($filters['overall_risk_level'])) {
            $query->where('overall_risk_level', $filters['overall_risk_level']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (($filters['review_overdue'] ?? null) === true || ($filters['review_overdue'] ?? null) === '1') {
            $query->whereNotNull('review_due_at')
                ->whereDate('review_due_at', '<', now()->toDateString())
                ->whereIn('status', [DpiaStatus::Approved->value, DpiaStatus::InProgress->value]);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id', 'assessment_number', 'title', 'status', 'template_code',
            'overall_risk_score', 'review_due_at', 'created_at', 'updated_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAssessment(array $data): DpiaAssessment
    {
        /** @var DpiaAssessment $assessment */
        $assessment = $this->model->newQuery()->create($data);

        return $assessment->fresh([
            'company',
            'assignee',
            'creator',
            'updater',
        ]) ?? $assessment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAssessment(DpiaAssessment $assessment, array $data): DpiaAssessment
    {
        $assessment->fill($data);
        $assessment->save();

        return $assessment->refresh()->load([
            'company',
            'assignee',
            'reviewer',
            'submitter',
            'approver',
            'rejector',
            'creator',
            'updater',
            'risks.owner',
            'risks.actions.performer',
        ]);
    }

    public function generateAssessmentNumber(): string
    {
        $prefix = 'DPIA-'.now()->format('Ymd').'-';

        $last = $this->model->newQuery()
            ->withTrashed()
            ->where('assessment_number', 'like', $prefix.'%')
            ->orderByDesc('assessment_number')
            ->value('assessment_number');

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

        $byTemplate = $base->clone()
            ->selectRaw('template_code, COUNT(*) as aggregate')
            ->groupBy('template_code')
            ->pluck('aggregate', 'template_code')
            ->all();

        return [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', DpiaStatus::Draft->value)->count(),
            'in_progress' => (clone $base)->where('status', DpiaStatus::InProgress->value)->count(),
            'pending_review' => (clone $base)->where('status', DpiaStatus::PendingReview->value)->count(),
            'approved' => (clone $base)->where('status', DpiaStatus::Approved->value)->count(),
            'rejected' => (clone $base)->where('status', DpiaStatus::Rejected->value)->count(),
            'archived' => (clone $base)->where('status', DpiaStatus::Archived->value)->count(),
            'active' => (clone $base)->whereIn('status', DpiaStatus::activeValues())->count(),
            'review_overdue' => (clone $base)
                ->whereNotNull('review_due_at')
                ->whereDate('review_due_at', '<', now()->toDateString())
                ->whereIn('status', [DpiaStatus::Approved->value, DpiaStatus::InProgress->value])
                ->count(),
            'by_status' => array_map('intval', $byStatus),
            'by_template' => array_map('intval', $byTemplate),
        ];
    }

    /**
     * @return Collection<int, DpiaAssessment>
     */
    public function recent(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email'])
            ->orderByDesc('updated_at')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, DpiaAssessment>
     */
    public function pendingApproval(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email'])
            ->where('status', DpiaStatus::PendingReview->value)
            ->orderBy('submitted_at')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }
}
