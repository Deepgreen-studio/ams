<?php

namespace App\Domains\Customers\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Events\CustomerCreated;
use App\Domains\Customers\Events\CustomerDeleted;
use App\Domains\Customers\Events\CustomerRestored;
use App\Domains\Customers\Events\CustomerUpdated;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CompanyRepository $companyRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{customers: LengthAwarePaginator, statistics: array<string, int>}
     */
    public function list(array $filters = []): array
    {
        $filters = $this->resolveCompanyFilter($filters);
        $companyId = isset($filters['company_id']) ? (int) $filters['company_id'] : null;

        return [
            'customers' => $this->customerRepository->paginateFiltered($filters),
            'statistics' => $this->customerRepository->statistics($companyId),
        ];
    }

    public function find(string $identifier, bool $withTrashed = false): Customer
    {
        return $this->customerRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): Customer
    {
        $customer = $this->find($identifier);

        return $customer->load([
            'company:id,uuid,company_name,status',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($data, $actor): Customer {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['status'] = $payload['status'] ?? CustomerStatus::Active->value;
            $payload['timezone'] = $payload['timezone'] ?? 'UTC';
            $payload['language'] = $payload['language'] ?? 'en';
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $customer = $this->customerRepository->createCustomer($payload);
            event(new CustomerCreated($customer, $actor));

            return $customer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Customer {
            $customer = $this->customerRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('company_id', $data) && ! blank($data['company_id'])) {
                $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
                $payload['company_id'] = $company->id;
            }

            $updated = $this->customerRepository->updateCustomer($customer, $payload);
            event(new CustomerUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $customer = $this->customerRepository->findByIdentifierOrFail($identifier);
            $this->customerRepository->updateCustomer($customer, ['updated_by' => $actor->id]);
            $customer->delete();
            event(new CustomerDeleted($customer, $actor));
        });
    }

    public function restore(string $identifier, User $actor): Customer
    {
        return DB::transaction(function () use ($identifier, $actor): Customer {
            $customer = $this->customerRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $customer->trashed()) {
                throw new ApiException('Customer is not archived.', 422);
            }

            $customer->restore();
            $restored = $this->customerRepository->updateCustomer($customer, ['updated_by' => $actor->id]);
            event(new CustomerRestored($restored, $actor));

            return $restored;
        });
    }

    /**
     * @return array<string, int>
     */
    public function statistics(?string $companyIdentifier = null): array
    {
        $companyId = null;

        if (! blank($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail($companyIdentifier);
            $companyId = $company->id;
        }

        return $this->customerRepository->statistics($companyId);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function resolveCompanyFilter(array $filters): array
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;

        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier);
            $filters['company_id'] = $company->id;
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'customer_type',
            'first_name',
            'last_name',
            'company_name',
            'email',
            'phone',
            'website',
            'industry',
            'country',
            'timezone',
            'language',
            'status',
            'notes',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['first_name', 'last_name', 'company_name', 'phone', 'website', 'industry', 'country', 'notes'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if (array_key_exists('email', $payload) && is_string($payload['email'])) {
            $payload['email'] = strtolower(trim($payload['email']));
        }

        if (! $isUpdate && empty($payload['timezone'])) {
            $payload['timezone'] = 'UTC';
        }

        if (! $isUpdate && empty($payload['language'])) {
            $payload['language'] = 'en';
        }

        if (isset($payload['customer_type'])) {
            $type = $payload['customer_type'] instanceof CustomerType
                ? $payload['customer_type']
                : CustomerType::tryFrom((string) $payload['customer_type']);

            if ($type?->requiresPersonName()) {
                $payload['company_name'] = $payload['company_name'] ?? null;
            }
        }

        return $payload;
    }
}
