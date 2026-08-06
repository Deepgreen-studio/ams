<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\CustomerApplication;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerApplicationRepository extends BaseRepository
{
    public function __construct(CustomerApplication $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?CustomerApplication
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var CustomerApplication|null $assignment */
        $assignment = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $assignment;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): CustomerApplication
    {
        $assignment = $this->findByIdentifier($identifier, $withTrashed);

        if (! $assignment) {
            abort(404, 'Customer application assignment not found.');
        }

        return $assignment;
    }

    public function findActiveAssignment(int $customerId, int $applicationId, ?int $exceptId = null): ?CustomerApplication
    {
        $query = $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->where('application_id', $applicationId);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        /** @var CustomerApplication|null $assignment */
        $assignment = $query->first();

        return $assignment;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'customer:id,uuid,first_name,last_name,company_name,email,customer_type,status,company_id',
                'application:id,uuid,name,slug,platform,status,company_id,integration_id',
                'environment:id,uuid,name,slug,type,status,application_id',
                'integration:id,uuid,name,slug,status,type',
                'ownerContact:id,uuid,name,email,contact_type,status',
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

        if (! empty($filters['application_id'])) {
            $query->where('application_id', $filters['application_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->whereHas('application', function (Builder $applicationQuery) use ($search): void {
                    $applicationQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                })->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                    $customerQuery->where('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['ownership_type'])) {
            $query->where('ownership_type', $filters['ownership_type']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'status',
            'ownership_type',
            'activated_at',
            'expires_at',
            'created_at',
            'updated_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAssignment(array $data): CustomerApplication
    {
        /** @var CustomerApplication $assignment */
        $assignment = $this->model->newQuery()->create($data);

        return $assignment->fresh([
            'customer',
            'application',
            'environment',
            'integration',
            'ownerContact',
            'creator',
            'updater',
        ]) ?? $assignment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAssignment(CustomerApplication $assignment, array $data): CustomerApplication
    {
        $assignment->fill($data);
        $assignment->save();

        return $assignment->refresh()->load([
            'customer',
            'application',
            'environment',
            'integration',
            'ownerContact',
            'creator',
            'updater',
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(CustomerApplication $assignment, int $limit = 50): Collection
    {
        $activityModel = config('activitylog.activity_model');

        return $activityModel::query()
            ->forSubject($assignment)
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
