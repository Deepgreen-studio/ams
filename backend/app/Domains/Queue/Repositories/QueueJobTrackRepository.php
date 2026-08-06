<?php

namespace App\Domains\Queue\Repositories;

use App\Domains\Queue\Models\QueueJobTrack;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QueueJobTrackRepository extends BaseRepository
{
    public function __construct(QueueJobTrack $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): QueueJobTrack
    {
        /** @var QueueJobTrack|null $track */
        $track = $this->model->newQuery()->where('uuid', $uuid)->first();
        if (! $track) {
            abort(404, 'Queue job track not found.');
        }

        return $track;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createTrack(array $payload): QueueJobTrack
    {
        /** @var QueueJobTrack $track */
        $track = $this->model->newQuery()->create($payload);

        return $track;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateTrack(QueueJobTrack $track, array $payload): QueueJobTrack
    {
        $track->update($payload);

        return $track->fresh() ?? $track;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with(['company:id,uuid,company_name', 'actor:id,uuid,full_name,email'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['queue'])) {
            $query->where('queue', $filters['queue']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('display_name', 'like', "%{$search}%")
                    ->orWhere('job_class', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%")
                    ->orWhere('queue', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $rows = $this->model->newQuery()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return [
            'queued' => (int) ($rows['queued'] ?? 0),
            'running' => (int) ($rows['running'] ?? 0),
            'completed' => (int) ($rows['completed'] ?? 0),
            'failed' => (int) ($rows['failed'] ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function typeCounts(): array
    {
        return $this->model->newQuery()
            ->select('type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->map(fn ($value) => (int) $value)
            ->all();
    }
}
