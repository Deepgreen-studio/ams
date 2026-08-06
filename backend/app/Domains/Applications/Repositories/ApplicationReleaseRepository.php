<?php

namespace App\Domains\Applications\Repositories;

use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Applications\Models\ApplicationReleaseNote;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ApplicationReleaseRepository extends BaseRepository
{
    public function __construct(ApplicationRelease $model)
    {
        parent::__construct($model);
    }

    public function findForApplication(int $applicationId, string $identifier, bool $withTrashed = false): ApplicationRelease
    {
        $query = $this->model->newQuery()->where('application_id', $applicationId);

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ApplicationRelease|null $release */
        $release = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $release) {
            abort(404, 'Application release not found.');
        }

        return $release;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForApplication(int $applicationId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($applicationId, $filters)
            ->with($this->defaultRelations())
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, ApplicationRelease>
     */
    public function dashboardForApplication(int $applicationId): Collection
    {
        return $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->with($this->defaultRelations())
            ->orderByDesc('scheduled_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, ApplicationRelease>
     */
    public function calendarForApplication(int $applicationId, Carbon $from, Carbon $to): Collection
    {
        return $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->where(function (Builder $query) use ($from, $to): void {
                $query->whereBetween('scheduled_at', [$from, $to])
                    ->orWhereBetween('deployment_date', [$from, $to]);
            })
            ->with($this->defaultRelations())
            ->orderBy('scheduled_at')
            ->orderBy('deployment_date')
            ->get();
    }

    /**
     * @return Collection<int, ApplicationRelease>
     */
    public function timelineForApplication(int $applicationId, int $limit = 40): Collection
    {
        return $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->with($this->defaultRelations())
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function summaryForApplication(int $applicationId): array
    {
        $statuses = $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $pendingApprovals = $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->where('approval_status', 'pending')
            ->count();

        return [
            'total' => array_sum($statuses),
            'planned' => (int) ($statuses['planned'] ?? 0),
            'scheduled' => (int) ($statuses['scheduled'] ?? 0),
            'pending_approval' => (int) ($statuses['pending_approval'] ?? 0),
            'approved' => (int) ($statuses['approved'] ?? 0),
            'deployed' => (int) ($statuses['deployed'] ?? 0),
            'failed' => (int) ($statuses['failed'] ?? 0),
            'rolled_back' => (int) ($statuses['rolled_back'] ?? 0),
            'awaiting_approval' => $pendingApprovals,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(int $applicationId, array $filters = []): Builder
    {
        $query = $this->model->newQuery()->where('application_id', $applicationId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['release_type'])) {
            $query->where('release_type', $filters['release_type']);
        }

        if (! empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        if (! empty($filters['rollback_status'])) {
            $query->where('rollback_status', $filters['rollback_status']);
        }

        if (! empty($filters['environment_id'])) {
            $query->where('environment_id', (int) $filters['environment_id']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('version_label', 'like', "%{$search}%")
                    ->orWhere('plan_summary', 'like', "%{$search}%");
            });
        }

        $sortBy = in_array($filters['sort_by'] ?? '', [
            'name', 'version_label', 'status', 'scheduled_at', 'deployment_date', 'created_at', 'updated_at',
        ], true) ? $filters['sort_by'] : 'created_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRelease(array $data): ApplicationRelease
    {
        /** @var ApplicationRelease $release */
        $release = $this->model->newQuery()->create($data);

        return $release->fresh($this->defaultRelations()) ?? $release;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRelease(ApplicationRelease $release, array $data): ApplicationRelease
    {
        $release->update($data);

        return $release->refresh()->load($this->defaultRelations());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createNote(array $data): ApplicationReleaseNote
    {
        /** @var ApplicationReleaseNote $note */
        $note = ApplicationReleaseNote::query()->create($data);

        return $note;
    }

    public function deleteNotesForRelease(int $releaseId): void
    {
        ApplicationReleaseNote::query()->where('release_id', $releaseId)->delete();
    }

    /**
     * @return list<string>
     */
    protected function defaultRelations(): array
    {
        return [
            'version:id,uuid,version_number,status,build_number',
            'environment:id,uuid,name,slug,type',
            'notes',
            'approver:id,uuid,full_name,email',
            'rolledBackBy:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ];
    }
}
