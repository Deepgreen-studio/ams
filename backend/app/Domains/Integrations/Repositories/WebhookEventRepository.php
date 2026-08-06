<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\WebhookEvent;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WebhookEventRepository extends BaseRepository
{
    public function __construct(WebhookEvent $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name): ?WebhookEvent
    {
        /** @var WebhookEvent|null $event */
        $event = $this->model->newQuery()->where('name', $name)->first();

        return $event;
    }

    public function findByUuidOrFail(string $uuid): WebhookEvent
    {
        /** @var WebhookEvent|null $event */
        $event = $this->model->newQuery()->where('uuid', $uuid)->first();
        if (! $event) {
            abort(404, 'Webhook event not found.');
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 50), 100));

        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['source_module'])) {
            $query->where('source_module', $filters['source_module']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('source_module')->orderBy('name');
    }

    /**
     * @return Collection<int, WebhookEvent>
     */
    public function allActive(): Collection
    {
        return $this->model->newQuery()->where('status', 'active')->orderBy('name')->get();
    }
}
