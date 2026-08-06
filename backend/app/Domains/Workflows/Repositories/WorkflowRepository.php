<?php

namespace App\Domains\Workflows\Repositories;

use App\Domains\Workflows\Enums\WorkflowDefinitionStatus;
use App\Domains\Workflows\Models\Workflow;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WorkflowRepository extends BaseRepository
{
    public function __construct(Workflow $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Workflow
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Workflow|null $workflow */
        $workflow = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $workflow;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Workflow
    {
        $workflow = $this->findByIdentifier($identifier, $withTrashed);
        if (! $workflow) {
            abort(404, 'Workflow not found.');
        }

        return $workflow;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with([
                'company:id,uuid,company_name',
                'steps',
                'creator:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'active' => $this->model->newQuery()->where('status', WorkflowDefinitionStatus::Active->value)->count(),
            'draft' => $this->model->newQuery()->where('status', WorkflowDefinitionStatus::Draft->value)->count(),
            'enabled' => $this->model->newQuery()->where('is_enabled', true)->count(),
            'archived' => $this->model->newQuery()->where('status', WorkflowDefinitionStatus::Archived->value)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('id');

        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (array_key_exists('is_enabled', $filters) && $filters['is_enabled'] !== null && $filters['is_enabled'] !== '') {
            $query->where('is_enabled', filter_var($filters['is_enabled'], FILTER_VALIDATE_BOOLEAN));
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
