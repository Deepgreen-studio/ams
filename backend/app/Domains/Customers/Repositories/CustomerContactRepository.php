<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\CustomerContact;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerContactRepository extends BaseRepository
{
    public function __construct(CustomerContact $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?CustomerContact
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var CustomerContact|null $contact */
        $contact = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $contact;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): CustomerContact
    {
        $contact = $this->findByIdentifier($identifier, $withTrashed);

        if (! $contact) {
            abort(404, 'Customer contact not found.');
        }

        return $contact;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'customer:id,uuid,first_name,last_name,company_name,email,customer_type,status',
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

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['contact_type'])) {
            $query->where('contact_type', $filters['contact_type']);
        }

        if (! empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'name',
            'email',
            'contact_type',
            'status',
            'department',
            'position',
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
    public function createContact(array $data): CustomerContact
    {
        /** @var CustomerContact $contact */
        $contact = $this->model->newQuery()->create($data);

        return $contact->fresh(['customer', 'creator', 'updater']) ?? $contact;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateContact(CustomerContact $contact, array $data): CustomerContact
    {
        $contact->fill($data);
        $contact->save();

        return $contact->refresh()->load(['customer', 'creator', 'updater']);
    }

    public function clearPrimaryForCustomer(int $customerId, ?int $exceptId = null): void
    {
        $query = $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->where('contact_type', 'primary');

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['contact_type' => 'support']);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(CustomerContact $contact, int $limit = 50): Collection
    {
        $activityModel = config('activitylog.activity_model');

        return $activityModel::query()
            ->forSubject($contact)
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
