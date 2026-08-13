<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Enums\CannedResponseVisibility;
use App\Domains\Support\Models\SupportCannedResponse;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupportCannedResponseRepository
{
    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): SupportCannedResponse
    {
        $query = SupportCannedResponse::query()->when($withTrashed, fn (Builder $q) => $q->withTrashed());

        return $query
            ->where(function (Builder $inner) use ($identifier): void {
                $inner->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $inner->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));
        $sortBy = in_array($filters['sort_by'] ?? null, ['title', 'usage_count', 'sort_order', 'created_at', 'updated_at'], true)
            ? (string) $filters['sort_by']
            : 'sort_order';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = SupportCannedResponse::query()
            ->with(['owner:id,uuid,full_name,email'])
            ->accessibleBy($user);

        if (($filters['visibility'] ?? null) === CannedResponseVisibility::Personal->value) {
            $query->where('visibility', CannedResponseVisibility::Personal->value)
                ->where('user_id', $user->id);
        } elseif (($filters['visibility'] ?? null) === CannedResponseVisibility::Shared->value) {
            $query->where('visibility', CannedResponseVisibility::Shared->value);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('shortcut', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('title')
            ->paginate(
                $perPage,
                ['*'],
                'page',
                max(1, (int) ($filters['page'] ?? 1)),
            );
    }

    /**
     * @return array<string, int>
     */
    public function statisticsForUser(User $user): array
    {
        $base = SupportCannedResponse::query()->accessibleBy($user);

        return [
            'total' => (clone $base)->count(),
            'personal' => (clone $base)
                ->where('visibility', CannedResponseVisibility::Personal->value)
                ->where('user_id', $user->id)
                ->count(),
            'shared' => (clone $base)
                ->where('visibility', CannedResponseVisibility::Shared->value)
                ->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupportCannedResponse
    {
        return SupportCannedResponse::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupportCannedResponse $response, array $data): SupportCannedResponse
    {
        $response->fill($data);
        $response->save();

        return $response->refresh();
    }

    public function delete(SupportCannedResponse $response): void
    {
        $response->delete();
    }

    public function incrementUsage(SupportCannedResponse $response): SupportCannedResponse
    {
        $response->increment('usage_count');

        return $response->refresh();
    }
}
