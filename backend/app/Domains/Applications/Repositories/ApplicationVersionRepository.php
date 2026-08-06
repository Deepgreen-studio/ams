<?php

namespace App\Domains\Applications\Repositories;

use App\Domains\Applications\Models\ApplicationVersion;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApplicationVersionRepository extends BaseRepository
{
    public function __construct(ApplicationVersion $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?ApplicationVersion
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ApplicationVersion|null $version */
        $version = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $version;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): ApplicationVersion
    {
        $version = $this->findByIdentifier($identifier, $withTrashed);

        if (! $version) {
            abort(404, 'Application version not found.');
        }

        return $version;
    }

    public function findForApplication(int $applicationId, string $identifier, bool $withTrashed = false): ApplicationVersion
    {
        $query = $this->model->newQuery()->where('application_id', $applicationId);

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ApplicationVersion|null $version */
        $version = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $version) {
            abort(404, 'Application version not found.');
        }

        return $version;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForApplication(int $applicationId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($applicationId, $filters)
            ->with([
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(int $applicationId, array $filters = []): Builder
    {
        $query = $this->model->newQuery()->where('application_id', $applicationId);

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('version_number', 'like', "%{$search}%")
                    ->orWhere('build_number', 'like', "%{$search}%")
                    ->orWhere('release_notes', 'like', "%{$search}%")
                    ->orWhere('minimum_supported_version', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'semver');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'semver') {
            return $query
                ->orderBy('major', $sortDir)
                ->orderBy('minor', $sortDir)
                ->orderBy('patch', $sortDir);
        }

        $allowed = ['version_number', 'build_number', 'status', 'release_date', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return Collection<int, ApplicationVersion>
     */
    public function timelineForApplication(int $applicationId): Collection
    {
        return $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->orderByDesc('major')
            ->orderByDesc('minor')
            ->orderByDesc('patch')
            ->get();
    }

    /**
     * @return Collection<int, ApplicationVersion>
     */
    public function historyForApplication(int $applicationId): Collection
    {
        return $this->model->newQuery()
            ->withTrashed()
            ->where('application_id', $applicationId)
            ->with([
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function versionNumberExists(int $applicationId, string $versionNumber, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->where('version_number', $versionNumber)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createVersion(array $data): ApplicationVersion
    {
        /** @var ApplicationVersion $version */
        $version = $this->model->newQuery()->create($data);

        return $version->fresh(['application', 'creator', 'updater']) ?? $version;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateVersion(ApplicationVersion $version, array $data): ApplicationVersion
    {
        $version->fill($data);
        $version->save();

        return $version->refresh()->load(['application', 'creator', 'updater']);
    }

    public function demoteOtherProductionVersions(int $applicationId, int $keepId): void
    {
        $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->where('id', '!=', $keepId)
            ->where('status', 'production')
            ->whereNull('deleted_at')
            ->update(['status' => 'deprecated']);
    }
}
