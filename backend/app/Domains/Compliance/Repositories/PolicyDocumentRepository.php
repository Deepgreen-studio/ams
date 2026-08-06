<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\PolicyDocumentStatus;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PolicyDocumentRepository extends BaseRepository
{
    public function __construct(PolicyDocument $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?PolicyDocument
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var PolicyDocument|null $policy */
        $policy = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('policy_number', $identifier)
                ->orWhere('slug', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $policy;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): PolicyDocument
    {
        $policy = $this->findByIdentifier($identifier, $withTrashed);

        if (! $policy) {
            abort(404, 'Policy document not found.');
        }

        return $policy;
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
                'content:id,uuid,title,version,slug',
                'assignee:id,uuid,full_name,email',
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

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('policy_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['policy_type'])) {
            $query->where('policy_type', $filters['policy_type']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id', 'policy_number', 'title', 'status', 'policy_type',
            'current_version', 'published_at', 'review_due_at', 'created_at', 'updated_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPolicy(array $data): PolicyDocument
    {
        /** @var PolicyDocument $policy */
        $policy = $this->model->newQuery()->create($data);

        return $policy->fresh([
            'company',
            'content',
            'assignee',
            'creator',
            'updater',
        ]) ?? $policy;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePolicy(PolicyDocument $policy, array $data): PolicyDocument
    {
        $policy->fill($data);
        $policy->save();

        return $policy->refresh()->load([
            'company',
            'content',
            'assignee',
            'creator',
            'updater',
            'versions.creator',
            'approvals.requester',
            'approvals.reviewer',
            'approvals.version',
        ]);
    }

    public function generatePolicyNumber(): string
    {
        $prefix = 'POL-'.now()->format('Ymd').'-';

        $last = $this->model->newQuery()
            ->withTrashed()
            ->where('policy_number', 'like', $prefix.'%')
            ->orderByDesc('policy_number')
            ->value('policy_number');

        $sequence = 1;

        if (is_string($last) && preg_match('/(\d{5})$/', $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function uniqueSlug(int $companyId, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'policy';
        $slug = $base;
        $i = 1;

        while ($this->slugExists($companyId, $slug, $ignoreId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function slugExists(int $companyId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('company_id', $companyId)
            ->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
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
            ->selectRaw('policy_type, COUNT(*) as aggregate')
            ->groupBy('policy_type')
            ->pluck('aggregate', 'policy_type')
            ->all();

        return [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', PolicyDocumentStatus::Draft->value)->count(),
            'review' => (clone $base)->where('status', PolicyDocumentStatus::Review->value)->count(),
            'approved' => (clone $base)->where('status', PolicyDocumentStatus::Approved->value)->count(),
            'published' => (clone $base)->where('status', PolicyDocumentStatus::Published->value)->count(),
            'archived' => (clone $base)->where('status', PolicyDocumentStatus::Archived->value)->count(),
            'cms_linked' => (clone $base)->whereNotNull('content_id')->count(),
            'review_overdue' => (clone $base)
                ->whereNotNull('review_due_at')
                ->whereDate('review_due_at', '<', now()->toDateString())
                ->whereIn('status', PolicyDocumentStatus::activeValues())
                ->count(),
            'by_status' => array_map('intval', $byStatus),
            'by_type' => array_map('intval', $byType),
        ];
    }

    /**
     * @return Collection<int, PolicyDocument>
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
}
