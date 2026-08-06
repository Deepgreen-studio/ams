<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\Customer;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Customer
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Customer|null $customer */
        $customer = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $customer;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Customer
    {
        $customer = $this->findByIdentifier($identifier, $withTrashed);

        if (! $customer) {
            abort(404, 'Customer not found.');
        }

        return $customer;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'company:id,uuid,company_name,status',
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

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('industry', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_type'])) {
            $query->where('customer_type', $filters['customer_type']);
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (! empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'first_name',
            'last_name',
            'company_name',
            'email',
            'customer_type',
            'status',
            'country',
            'created_at',
            'updated_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return array<string, int>
     */
    public function statistics(?int $companyId = null): array
    {
        $base = $this->model->newQuery();

        if ($companyId !== null) {
            $base->where('company_id', $companyId);
        }

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'suspended' => (clone $base)->where('status', 'suspended')->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'individual' => (clone $base)->where('customer_type', 'individual')->count(),
            'business' => (clone $base)->where('customer_type', 'business')->count(),
            'enterprise' => (clone $base)->where('customer_type', 'enterprise')->count(),
            'trashed' => (clone $base)->onlyTrashed()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCustomer(array $data): Customer
    {
        /** @var Customer $customer */
        $customer = $this->model->newQuery()->create($data);

        return $customer->fresh(['company', 'creator', 'updater']) ?? $customer;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer->fill($data);
        $customer->save();

        return $customer->refresh()->load(['company', 'creator', 'updater']);
    }
}
