<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Enums\CustomerDocumentCategory;
use App\Domains\Customers\Models\CustomerDocument;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerDocumentRepository extends BaseRepository
{
    public function __construct(CustomerDocument $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?CustomerDocument
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var CustomerDocument|null $document */
        $document = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $document;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): CustomerDocument
    {
        $document = $this->findByIdentifier($identifier, $withTrashed);

        if (! $document) {
            abort(404, 'Customer document not found.');
        }

        return $document;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'customer:id,uuid,first_name,last_name,company_name,email,customer_type,status',
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

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('is_current', $filters) && $filters['is_current'] !== null && $filters['is_current'] !== '') {
            $query->where('is_current', filter_var($filters['is_current'], FILTER_VALIDATE_BOOLEAN));
        } elseif (($filters['include_versions'] ?? null) !== true && ($filters['include_versions'] ?? null) !== '1') {
            $query->where('is_current', true);
        }

        if (! empty($filters['search'])) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['search']).'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', $search)
                    ->orWhere('original_filename', 'like', $search)
                    ->orWhere('notes', 'like', $search);
            });
        }

        if (($filters['expiring_soon'] ?? null) === true || ($filters['expiring_soon'] ?? null) === '1') {
            $query->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(30)]);
        }

        $sortBy = in_array($filters['sort_by'] ?? 'created_at', [
            'created_at', 'updated_at', 'name', 'category', 'status', 'expires_at', 'version', 'size',
        ], true) ? ($filters['sort_by'] ?? 'created_at') : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return list<array{category: string, label: string, count: int}>
     */
    public function folders(?int $customerId = null): array
    {
        $base = $this->model->newQuery()->where('is_current', true);

        if ($customerId !== null) {
            $base->where('customer_id', $customerId);
        }

        $counts = (clone $base)
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        return array_map(static function (CustomerDocumentCategory $category) use ($counts): array {
            return [
                'category' => $category->value,
                'label' => $category->label(),
                'count' => (int) ($counts[$category->value] ?? 0),
            ];
        }, CustomerDocumentCategory::cases());
    }

    /**
     * @return array<string, int>
     */
    public function statistics(?int $customerId = null): array
    {
        $base = $this->model->newQuery()->where('is_current', true);

        if ($customerId !== null) {
            $base->where('customer_id', $customerId);
        }

        return [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', 'draft')->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'expired' => (clone $base)->where('status', 'expired')->count(),
            'expiring_soon' => (clone $base)
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(30)])
                ->count(),
        ];
    }

    /**
     * @return Collection<int, CustomerDocument>
     */
    public function versionsForGroup(string $documentGroupUuid, int $customerId): Collection
    {
        return $this->model->newQuery()
            ->withTrashed()
            ->where('document_group_uuid', $documentGroupUuid)
            ->where('customer_id', $customerId)
            ->orderByDesc('version')
            ->get();
    }

    public function latestVersionNumber(string $documentGroupUuid): int
    {
        return (int) $this->model->newQuery()
            ->withTrashed()
            ->where('document_group_uuid', $documentGroupUuid)
            ->max('version');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDocument(array $data): CustomerDocument
    {
        /** @var CustomerDocument $document */
        $document = $this->model->newQuery()->create($data);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDocument(CustomerDocument $document, array $data): CustomerDocument
    {
        $document->fill($data);
        $document->save();

        return $document->refresh();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(CustomerDocument $document, int $limit = 50): Collection
    {
        return activity()
            ->forSubject($document)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn ($item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'event' => $item->properties['event'] ?? null,
                'properties' => $item->properties,
                'causer' => $item->causer ? [
                    'id' => $item->causer->id,
                    'uuid' => $item->causer->uuid ?? null,
                    'full_name' => $item->causer->full_name ?? null,
                ] : null,
                'created_at' => $item->created_at,
            ]);
    }
}
