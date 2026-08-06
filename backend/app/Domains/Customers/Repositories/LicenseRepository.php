<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\License;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LicenseRepository extends BaseRepository
{
    public function __construct(License $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?License
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var License|null $license */
        $license = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('license_key', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $license;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): License
    {
        $license = $this->findByIdentifier($identifier, $withTrashed);

        if (! $license) {
            abort(404, 'License not found.');
        }

        return $license;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'subscription:id,uuid,plan_name,plan_type,status,payment_status,customer_id',
                'customer:id,uuid,first_name,last_name,company_name,email',
                'customerApplication:id,uuid,application_id,status',
                'customerApplication.application:id,uuid,name,slug',
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
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['subscription_id'])) {
            $query->where('subscription_id', $filters['subscription_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('license_key', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('revoked_reason', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'license_key', 'status', 'starts_at', 'expires_at', 'created_at', 'updated_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return array<string, int>
     */
    public function statistics(?int $customerId = null): array
    {
        $base = $this->model->newQuery();

        if ($customerId !== null) {
            $base->where('customer_id', $customerId);
        }

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'revoked' => (clone $base)->where('status', 'revoked')->count(),
            'expired' => (clone $base)->where('status', 'expired')->count(),
            'trashed' => (clone $base)->onlyTrashed()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLicense(array $data): License
    {
        /** @var License $license */
        $license = $this->model->newQuery()->create($data);

        return $license->fresh([
            'subscription',
            'customer',
            'customerApplication.application',
            'creator',
            'updater',
        ]) ?? $license;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateLicense(License $license, array $data): License
    {
        $license->fill($data);
        $license->save();

        return $license->refresh()->load([
            'subscription',
            'customer',
            'customerApplication.application',
            'creator',
            'updater',
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(License $license, int $limit = 50): Collection
    {
        $activityModel = config('activitylog.activity_model');

        return $activityModel::query()
            ->forSubject($license)
            ->with(['causer:id,uuid,full_name,email'])
            ->latest()
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(static function ($activity): array {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'log_name' => $activity->log_name,
                    'created_at' => $activity->created_at,
                    'properties' => $activity->properties,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'uuid' => $activity->causer->uuid,
                        'full_name' => $activity->causer->full_name,
                        'email' => $activity->causer->email,
                    ] : null,
                ];
            });
    }
}
