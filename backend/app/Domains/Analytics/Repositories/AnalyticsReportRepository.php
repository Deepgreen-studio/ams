<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\Models\AnalyticsReport;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AnalyticsReportRepository extends BaseRepository
{
    public function __construct(AnalyticsReport $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid, bool $withTrashed = false): AnalyticsReport
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var AnalyticsReport $report */
        $report = $query->where('uuid', $uuid)->firstOrFail();

        return $report;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
                'owner:id,uuid,full_name,email',
            ])
            ->withCount('runs')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', (int) $filters['owner_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['report_type'])) {
            $query->where('report_type', $filters['report_type']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (array_key_exists('is_saved', $filters) && $filters['is_saved'] !== '' && $filters['is_saved'] !== null) {
            $query->where('is_saved', filter_var($filters['is_saved'], FILTER_VALIDATE_BOOLEAN));
        }

        if (array_key_exists('is_scheduled', $filters) && $filters['is_scheduled'] !== '' && $filters['is_scheduled'] !== null) {
            $query->where('is_scheduled', filter_var($filters['is_scheduled'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('updated_at');
    }

    public function uniqueSlug(string $name, ?int $companyId = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'report';
        $slug = $base;
        $i = 1;

        while ($this->slugExists($slug, $companyId, $ignoreId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?int $companyId, ?int $ignoreId): bool
    {
        $query = $this->model->newQuery()->withTrashed()->where('slug', $slug);

        if ($companyId === null) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $companyId);
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
