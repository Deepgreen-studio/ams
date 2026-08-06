<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Enums\CustomerContactStatus;
use App\Domains\Customers\Enums\CustomerContactType;
use App\Domains\Customers\Events\CustomerContactCreated;
use App\Domains\Customers\Events\CustomerContactDeleted;
use App\Domains\Customers\Events\CustomerContactRestored;
use App\Domains\Customers\Events\CustomerContactUpdated;
use App\Domains\Customers\Models\CustomerContact;
use App\Domains\Customers\Repositories\CustomerContactRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerContactService
{
    public function __construct(
        private readonly CustomerContactRepository $customerContactRepository,
        private readonly CustomerRepository $customerRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->customerContactRepository->paginateFiltered(
            $this->resolveCustomerFilter($filters)
        );
    }

    public function find(string $identifier, bool $withTrashed = false): CustomerContact
    {
        return $this->customerContactRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): CustomerContact
    {
        $contact = $this->find($identifier);

        return $contact->load([
            'customer:id,uuid,first_name,last_name,company_name,email,customer_type,status,company_id',
            'customer.company:id,uuid,company_name',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CustomerContact
    {
        return DB::transaction(function () use ($data, $actor): CustomerContact {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $data['customer_id']);
            $payload = $this->preparePayload($data);
            $payload['customer_id'] = $customer->id;
            $payload['status'] = $payload['status'] ?? CustomerContactStatus::Active->value;
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            if (($payload['contact_type'] ?? null) === CustomerContactType::Primary->value) {
                $this->customerContactRepository->clearPrimaryForCustomer($customer->id);
            }

            $contact = $this->customerContactRepository->createContact($payload);
            event(new CustomerContactCreated($contact, $actor));

            return $contact;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): CustomerContact
    {
        return DB::transaction(function () use ($identifier, $data, $actor): CustomerContact {
            $contact = $this->customerContactRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (
                array_key_exists('contact_type', $payload)
                && $payload['contact_type'] === CustomerContactType::Primary->value
            ) {
                $this->customerContactRepository->clearPrimaryForCustomer($contact->customer_id, $contact->id);
            }

            $updated = $this->customerContactRepository->updateContact($contact, $payload);
            event(new CustomerContactUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $contact = $this->customerContactRepository->findByIdentifierOrFail($identifier);
            $this->customerContactRepository->updateContact($contact, ['updated_by' => $actor->id]);
            $contact->delete();
            event(new CustomerContactDeleted($contact, $actor));
        });
    }

    public function restore(string $identifier, User $actor): CustomerContact
    {
        return DB::transaction(function () use ($identifier, $actor): CustomerContact {
            $contact = $this->customerContactRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $contact->trashed()) {
                throw new ApiException('Customer contact is not archived.', 422);
            }

            $contact->restore();

            if ($contact->contact_type === CustomerContactType::Primary) {
                $this->customerContactRepository->clearPrimaryForCustomer($contact->customer_id, $contact->id);
            }

            $restored = $this->customerContactRepository->updateContact($contact, ['updated_by' => $actor->id]);
            event(new CustomerContactRestored($restored, $actor));

            return $restored;
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(string $identifier, int $limit = 50): Collection
    {
        $contact = $this->find($identifier);

        return $this->customerContactRepository->timeline($contact, $limit);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function resolveCustomerFilter(array $filters): array
    {
        $customerIdentifier = $filters['customer'] ?? $filters['customer_id'] ?? null;

        if (! empty($customerIdentifier) && ! is_numeric($customerIdentifier)) {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $customerIdentifier);
            $filters['customer_id'] = $customer->id;
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
            'contact_type',
            'name',
            'email',
            'phone',
            'position',
            'department',
            'status',
            'notes',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['email', 'phone', 'position', 'department', 'notes'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if (array_key_exists('email', $payload) && is_string($payload['email'])) {
            $payload['email'] = strtolower(trim($payload['email']));
        }

        if (! $isUpdate && empty($payload['status'])) {
            $payload['status'] = CustomerContactStatus::Active->value;
        }

        return $payload;
    }
}
