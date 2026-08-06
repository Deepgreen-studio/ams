<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardShareType;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Models\User;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnalyticsDashboardRepository extends BaseRepository
{
    public function __construct(AnalyticsDashboard $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid, bool $withTrashed = false): AnalyticsDashboard
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var AnalyticsDashboard $dashboard */
        $dashboard = $query->where('uuid', $uuid)->firstOrFail();

        return $dashboard;
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
                'company:id,uuid,company_name',
            ])
            ->withCount(['widgets', 'shares'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['trashed']) && $filters['trashed'] === 'only') {
            $query->onlyTrashed();
        } elseif (! empty($filters['trashed']) && $filters['trashed'] === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', (int) $filters['owner_id']);
        }

        if (! empty($filters['kind'])) {
            $query->where('kind', $filters['kind']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (array_key_exists('is_template', $filters) && $filters['is_template'] !== '' && $filters['is_template'] !== null) {
            $query->where('is_template', filter_var($filters['is_template'], FILTER_VALIDATE_BOOLEAN));
        }

        if (array_key_exists('is_system', $filters) && $filters['is_system'] !== '' && $filters['is_system'] !== null) {
            $query->where('is_system', filter_var($filters['is_system'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['accessible_by']) && $filters['accessible_by'] instanceof User) {
            $this->applyAccessibleScope($query, $filters['accessible_by'], $filters);
        }

        $sortBy = in_array($filters['sort_by'] ?? '', ['name', 'category', 'status', 'sort_order', 'created_at', 'updated_at', 'visibility'], true)
            ? $filters['sort_by']
            : 'sort_order';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sortBy, $sortDir)->orderBy('name');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyAccessibleScope(Builder $query, User $user, array $filters = []): void
    {
        if ($user->can('analytics.manage') || $user->hasRole('super-admin')) {
            return;
        }

        $roleIds = $user->roles()->pluck('id')->all();
        $companyIds = [];

        if (method_exists($user, 'companies')) {
            $companyIds = $user->companies()->pluck('companies.id')->all();
        } elseif (! empty($filters['company_id'])) {
            $companyIds = [(int) $filters['company_id']];
        }

        $query->where(function (Builder $builder) use ($user, $roleIds, $companyIds): void {
            $builder->where('owner_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhere('visibility', AnalyticsDashboardVisibility::System->value)
                ->orWhere(function (Builder $published): void {
                    $published->where('is_template', true)
                        ->where('status', AnalyticsDashboardStatus::Published->value);
                })
                ->orWhere(function (Builder $company) use ($companyIds): void {
                    $company->where('visibility', AnalyticsDashboardVisibility::Company->value)
                        ->where(function (Builder $scope) use ($companyIds): void {
                            $scope->whereNull('company_id');
                            if ($companyIds !== []) {
                                $scope->orWhereIn('company_id', $companyIds);
                            }
                        });
                })
                ->orWhereHas('shares', function (Builder $shares) use ($user, $roleIds, $companyIds): void {
                    $shares->where(function (Builder $shareScope) use ($user, $roleIds, $companyIds): void {
                        $shareScope->where(function (Builder $userShare) use ($user): void {
                            $userShare->where('share_type', AnalyticsDashboardShareType::User->value)
                                ->where('share_id', $user->id);
                        });

                        if ($roleIds !== []) {
                            $shareScope->orWhere(function (Builder $roleShare) use ($roleIds): void {
                                $roleShare->where('share_type', AnalyticsDashboardShareType::Role->value)
                                    ->whereIn('share_id', $roleIds);
                            });
                        }

                        if ($companyIds !== []) {
                            $shareScope->orWhere(function (Builder $companyShare) use ($companyIds): void {
                                $companyShare->where('share_type', AnalyticsDashboardShareType::Company->value)
                                    ->whereIn('share_id', $companyIds);
                            });
                        }
                    });
                });
        });
    }

    /**
     * @return Collection<int, AnalyticsDashboard>
     */
    public function templates(): Collection
    {
        return $this->model->newQuery()
            ->where(function (Builder $builder): void {
                $builder->where('is_template', true)
                    ->orWhere('visibility', AnalyticsDashboardVisibility::Template->value);
            })
            ->where('status', AnalyticsDashboardStatus::Published->value)
            ->withCount('widgets')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function uniqueSlug(string $name, ?int $companyId = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'dashboard';
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

    public function defaultPublished(?int $companyId = null): ?AnalyticsDashboard
    {
        $query = $this->model->newQuery()
            ->where('kind', AnalyticsDashboardKind::Dashboard->value)
            ->where('status', AnalyticsDashboardStatus::Published->value)
            ->where('is_default', true);

        if ($companyId) {
            $query->where(function (Builder $builder) use ($companyId): void {
                $builder->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        } else {
            $query->whereNull('company_id');
        }

        /** @var AnalyticsDashboard|null $dashboard */
        $dashboard = $query->orderByDesc('is_system')->first();

        return $dashboard;
    }
}
