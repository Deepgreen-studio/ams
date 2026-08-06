<?php

namespace App\Domains\Queue\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FailedJobRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $query = DB::table('failed_jobs')->orderByDesc('failed_at');

        if (! empty($filters['queue'])) {
            $query->where('queue', $filters['queue']);
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($builder) use ($search): void {
                $builder->where('uuid', 'like', "%{$search}%")
                    ->orWhere('queue', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhere('exception', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findByUuidOrFail(string $uuid): object
    {
        $job = DB::table('failed_jobs')->where('uuid', $uuid)->first();
        if (! $job) {
            abort(404, 'Failed job not found.');
        }

        return $job;
    }

    public function count(): int
    {
        return DB::table('failed_jobs')->count();
    }

    /**
     * @return Collection<int, object>
     */
    public function recent(int $limit = 8): Collection
    {
        return DB::table('failed_jobs')->orderByDesc('failed_at')->limit($limit)->get();
    }
}
