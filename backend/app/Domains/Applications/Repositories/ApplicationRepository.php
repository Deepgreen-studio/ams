<?php

namespace App\Domains\Applications\Repositories;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class ApplicationRepository extends BaseRepository
{
    public function __construct(Application $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Application
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Application|null $application */
        $application = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $application;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Application
    {
        $application = $this->findByIdentifier($identifier, $withTrashed);

        if (! $application) {
            abort(404, 'Application not found.');
        }

        return $application;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        if (($filters['group_by'] ?? null) === 'company' && ($filters['trashed'] ?? null) !== 'only') {
            return $this->paginateByCompany($filters);
        }

        $perPage = $this->perPage($filters);

        return $this->filteredQuery($filters)
            ->with($this->listRelations($filters, false))
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Page through companies that have matching applications, and return every
     * matching application for the companies on the current page.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateByCompany(array $filters = []): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters);
        $page = max(1, (int) ($filters['page'] ?? 1));

        $companyIds = $this->applyFilters($this->model->newQuery(), $filters)
            ->select('company_id')
            ->whereNotNull('company_id')
            ->distinct();

        $companies = Company::query()
            ->whereIn('companies.id', $companyIds)
            ->orderByDesc('companies.created_at')
            ->orderByDesc('companies.id')
            ->paginate($perPage, ['companies.*'], 'page', $page)
            ->withQueryString();

        $ids = $companies->getCollection()->modelKeys();
        $applications = $ids === []
            ? $this->model->newCollection()
            : $this->filteredQuery($filters)
                ->whereIn('company_id', $ids)
                ->with($this->listRelations($filters, true))
                ->get();

        $order = array_flip($ids);
        $applications = $applications
            ->sortBy(function (Application $application) use ($order): string {
                $rank = $order[$application->company_id] ?? PHP_INT_MAX;
                $created = $application->created_at?->getTimestamp() ?? 0;

                return sprintf('%08d-%020d', $rank, PHP_INT_MAX - $created);
            })
            ->values();

        return (new Paginator(
            $applications,
            $companies->total(),
            $companies->perPage(),
            $companies->currentPage(),
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        ))->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function perPage(array $filters): int
    {
        return max(1, min((int) ($filters['per_page'] ?? 15), 100));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function listRelations(array $filters, bool $constrainCounts): array
    {
        return [
            'company' => function ($query) use ($filters, $constrainCounts): void {
                $query->select('id', 'uuid', 'company_name', 'company_code', 'created_at');

                if ($constrainCounts) {
                    $query->withCount([
                        'applications' => function (Builder $apps) use ($filters): void {
                            $this->applyFilters($apps, $filters);
                        },
                    ]);

                    return;
                }

                $query->withCount('applications');
            },
            'integration:id,uuid,name,slug,status',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->applyFilters($this->model->newQuery(), $filters);

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'name',
            'slug',
            'platform',
            'category',
            'status',
            'visibility',
            'current_version',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
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

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('current_version', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['platform'])) {
            $platform = (string) $filters['platform'];
            $query->where(function (Builder $builder) use ($platform): void {
                $builder->where('platform', $platform)
                    ->orWhereJsonContains('platforms', $platform);
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createApplication(array $data): Application
    {
        /** @var Application $application */
        $application = $this->model->newQuery()->create($data);

        return $application->fresh(['company', 'integration', 'creator', 'updater']) ?? $application;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateApplication(Application $application, array $data): Application
    {
        $application->fill($data);
        $application->save();

        return $application->refresh()->load(['company', 'integration', 'creator', 'updater']);
    }

    public function slugExistsForCompany(int $companyId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()->withTrashed()
            ->where('company_id', $companyId)
            ->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function nameExistsForCompany(int $companyId, string $name, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        $base = $this->model->newQuery();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'draft' => (clone $base)->where('status', 'draft')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'archived' => (clone $base)->where('status', 'archived')->count(),
            'trashed' => (clone $base)->onlyTrashed()->count(),
        ];
    }
}
