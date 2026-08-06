<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\Webhook;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WebhookRepository extends BaseRepository
{
    public function __construct(Webhook $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Webhook
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Webhook|null $webhook */
        $webhook = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('slug', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $webhook;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Webhook
    {
        $webhook = $this->findByIdentifier($identifier, $withTrashed);
        if (! $webhook) {
            abort(404, 'Webhook not found.');
        }

        return $webhook;
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
                'integration:id,uuid,name,slug',
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

        if (! empty($filters['integration_id'])) {
            $query->where('integration_id', (int) $filters['integration_id']);
        }

        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'name', 'status', 'direction', 'created_at', 'updated_at', 'last_triggered_at'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return Collection<int, Webhook>
     */
    public function findActiveOutgoingForEvent(string $eventName, ?int $companyId = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('direction', 'outgoing')
            ->where('status', 'active')
            ->where(function (Builder $builder) use ($eventName): void {
                $builder->whereJsonContains('subscribed_events', $eventName)
                    ->orWhereJsonContains('subscribed_events', '*');
            });

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    public function slugExistsForCompany(int $companyId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createWebhook(array $data): Webhook
    {
        /** @var Webhook $webhook */
        $webhook = $this->model->newQuery()->create($data);

        return $webhook->fresh(['company', 'integration']) ?? $webhook;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateWebhook(Webhook $webhook, array $data): Webhook
    {
        $webhook->fill($data);
        $webhook->save();

        return $webhook->refresh()->load(['company', 'integration']);
    }
}
