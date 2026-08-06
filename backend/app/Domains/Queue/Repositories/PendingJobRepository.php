<?php

namespace App\Domains\Queue\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PendingJobRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $query = DB::table('jobs')->orderByDesc('id');

        if (! empty($filters['queue'])) {
            $query->where('queue', $filters['queue']);
        }
        if (! empty($filters['running_only'])) {
            $query->whereNotNull('reserved_at');
        }
        if (! empty($filters['pending_only'])) {
            $query->whereNull('reserved_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function counts(): array
    {
        $total = DB::table('jobs')->count();
        $running = DB::table('jobs')->whereNotNull('reserved_at')->count();

        return [
            'pending' => max(0, $total - $running),
            'running' => $running,
            'total' => $total,
        ];
    }
}
