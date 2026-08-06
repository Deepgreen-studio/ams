<?php

namespace App\Domains\Ai\Repositories;

use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Ai\Enums\AiPromptStatus;
use App\Domains\Ai\Models\AiPrompt;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AiPromptRepository extends BaseRepository
{
    public function __construct(AiPrompt $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?AiPrompt
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var AiPrompt|null $prompt */
        $prompt = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $prompt;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): AiPrompt
    {
        $prompt = $this->findByIdentifier($identifier, $withTrashed);
        if (! $prompt) {
            abort(404, 'AI prompt not found.');
        }

        return $prompt;
    }

    public function findPublishedByKey(string $key, ?int $companyId = null): ?AiPrompt
    {
        return $this->model->newQuery()
            ->where('key', $key)
            ->where('status', AiPromptStatus::Published->value)
            ->when($companyId !== null, function (Builder $builder) use ($companyId): void {
                $builder->where(function (Builder $inner) use ($companyId): void {
                    $inner->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('version')
            ->first();
    }

    public function findPublishedByFeature(AiFeature|string $feature, ?int $companyId = null): ?AiPrompt
    {
        $featureValue = $feature instanceof AiFeature ? $feature->value : $feature;

        return $this->model->newQuery()
            ->where('feature', $featureValue)
            ->where('status', AiPromptStatus::Published->value)
            ->when($companyId !== null, function (Builder $builder) use ($companyId): void {
                $builder->where(function (Builder $inner) use ($companyId): void {
                    $inner->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('version')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email'])
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
            'published' => $this->model->newQuery()->where('status', AiPromptStatus::Published->value)->count(),
            'draft' => $this->model->newQuery()->where('status', AiPromptStatus::Draft->value)->count(),
            'archived' => $this->model->newQuery()->where('status', AiPromptStatus::Archived->value)->count(),
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
        if (! blank($filters['feature'] ?? null)) {
            $query->where('feature', $filters['feature']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
