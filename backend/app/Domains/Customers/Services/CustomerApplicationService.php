<?php

namespace App\Domains\Customers\Services;

use App\Domains\Applications\Repositories\ApplicationEnvironmentRepository;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Domains\Customers\Enums\CustomerApplicationOwnershipType;
use App\Domains\Customers\Enums\CustomerApplicationStatus;
use App\Domains\Customers\Events\CustomerApplicationAssigned;
use App\Domains\Customers\Events\CustomerApplicationDeleted;
use App\Domains\Customers\Events\CustomerApplicationRestored;
use App\Domains\Customers\Events\CustomerApplicationUpdated;
use App\Domains\Customers\Models\CustomerApplication;
use App\Domains\Customers\Repositories\CustomerApplicationRepository;
use App\Domains\Customers\Repositories\CustomerContactRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerApplicationService
{
    public function __construct(
        private readonly CustomerApplicationRepository $customerApplicationRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerContactRepository $customerContactRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly ApplicationEnvironmentRepository $applicationEnvironmentRepository,
        private readonly IntegrationRepository $integrationRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->customerApplicationRepository->paginateFiltered(
            $this->resolveFilters($filters)
        );
    }

    /**
     * History includes archived assignments for audit/review.
     *
     * @param  array<string, mixed>  $filters
     */
    public function history(array $filters = []): LengthAwarePaginator
    {
        $filters['trashed'] = $filters['trashed'] ?? 'with';
        $filters['sort_by'] = $filters['sort_by'] ?? 'updated_at';
        $filters['sort_dir'] = $filters['sort_dir'] ?? 'desc';

        return $this->list($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): CustomerApplication
    {
        return $this->customerApplicationRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): CustomerApplication
    {
        $assignment = $this->find($identifier);

        return $assignment->load([
            'customer:id,uuid,first_name,last_name,company_name,email,customer_type,status,company_id',
            'customer.company:id,uuid,company_name',
            'application:id,uuid,name,slug,platform,status,visibility,company_id,integration_id',
            'environment:id,uuid,name,slug,type,status,application_id',
            'integration:id,uuid,name,slug,status,type',
            'ownerContact:id,uuid,name,email,contact_type,status,phone',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CustomerApplication
    {
        return DB::transaction(function () use ($data, $actor): CustomerApplication {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $data['customer_id']);
            $application = $this->applicationRepository->findByIdentifierOrFail((string) $data['application_id']);

            if ((int) $customer->company_id !== (int) $application->company_id) {
                throw new ApiException('Application must belong to the same company as the customer.', 422);
            }

            if ($this->customerApplicationRepository->findActiveAssignment($customer->id, $application->id)) {
                throw new ApiException('This application is already assigned to the customer.', 422);
            }

            $payload = $this->preparePayload($data);
            $payload['customer_id'] = $customer->id;
            $payload['application_id'] = $application->id;
            $payload['application_environment_id'] = $this->resolveEnvironmentId(
                $application->id,
                $data['application_environment_id'] ?? $data['environment_id'] ?? null
            );
            $payload['integration_id'] = $this->resolveIntegrationId(
                $data['integration_id'] ?? null,
                $application->integration_id
            );
            $payload['owner_contact_id'] = $this->resolveOwnerContactId(
                $customer->id,
                $data['owner_contact_id'] ?? null
            );
            $payload['ownership_type'] = $payload['ownership_type']
                ?? CustomerApplicationOwnershipType::CustomerOwned->value;
            $payload['status'] = $payload['status'] ?? CustomerApplicationStatus::Pending->value;
            $payload = $this->normalizeActivationDates($payload);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $assignment = $this->customerApplicationRepository->createAssignment($payload);
            event(new CustomerApplicationAssigned($assignment, $actor));

            return $assignment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): CustomerApplication
    {
        return DB::transaction(function () use ($identifier, $data, $actor): CustomerApplication {
            $assignment = $this->customerApplicationRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('application_environment_id', $data) || array_key_exists('environment_id', $data)) {
                $payload['application_environment_id'] = $this->resolveEnvironmentId(
                    $assignment->application_id,
                    $data['application_environment_id'] ?? $data['environment_id'] ?? null
                );
            }

            if (array_key_exists('integration_id', $data)) {
                $assignment->loadMissing('application');
                $payload['integration_id'] = $this->resolveIntegrationId(
                    $data['integration_id'],
                    $assignment->application?->integration_id
                );
            }

            if (array_key_exists('owner_contact_id', $data)) {
                $payload['owner_contact_id'] = $this->resolveOwnerContactId(
                    $assignment->customer_id,
                    $data['owner_contact_id']
                );
            }

            $payload = $this->normalizeActivationDates($payload, $assignment);
            $updated = $this->customerApplicationRepository->updateAssignment($assignment, $payload);
            event(new CustomerApplicationUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $assignment = $this->customerApplicationRepository->findByIdentifierOrFail($identifier);
            $this->customerApplicationRepository->updateAssignment($assignment, ['updated_by' => $actor->id]);
            $assignment->delete();
            event(new CustomerApplicationDeleted($assignment, $actor));
        });
    }

    public function restore(string $identifier, User $actor): CustomerApplication
    {
        return DB::transaction(function () use ($identifier, $actor): CustomerApplication {
            $assignment = $this->customerApplicationRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $assignment->trashed()) {
                throw new ApiException('Customer application assignment is not archived.', 422);
            }

            if ($this->customerApplicationRepository->findActiveAssignment(
                $assignment->customer_id,
                $assignment->application_id,
                $assignment->id
            )) {
                throw new ApiException('Cannot restore because this application is already assigned to the customer.', 422);
            }

            $assignment->restore();
            $restored = $this->customerApplicationRepository->updateAssignment($assignment, ['updated_by' => $actor->id]);
            event(new CustomerApplicationRestored($restored, $actor));

            return $restored;
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(string $identifier, int $limit = 50): Collection
    {
        $assignment = $this->find($identifier);

        return $this->customerApplicationRepository->timeline($assignment, $limit);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function resolveFilters(array $filters): array
    {
        $customerIdentifier = $filters['customer'] ?? $filters['customer_id'] ?? null;
        if (! empty($customerIdentifier) && ! is_numeric($customerIdentifier)) {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $customerIdentifier);
            $filters['customer_id'] = $customer->id;
        }

        $applicationIdentifier = $filters['application'] ?? $filters['application_id'] ?? null;
        if (! empty($applicationIdentifier) && ! is_numeric($applicationIdentifier)) {
            $application = $this->applicationRepository->findByIdentifierOrFail((string) $applicationIdentifier);
            $filters['application_id'] = $application->id;
        }

        return $filters;
    }

    protected function resolveEnvironmentId(int $applicationId, mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        $environment = $this->applicationEnvironmentRepository->findForApplication(
            $applicationId,
            (string) $identifier
        );

        return $environment->id;
    }

    protected function resolveIntegrationId(mixed $identifier, mixed $fallbackId = null): ?int
    {
        if ($identifier === null || $identifier === '') {
            return $fallbackId !== null ? (int) $fallbackId : null;
        }

        $integration = $this->integrationRepository->findByIdentifierOrFail((string) $identifier);

        return $integration->id;
    }

    protected function resolveOwnerContactId(int $customerId, mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        $contact = $this->customerContactRepository->findByIdentifierOrFail((string) $identifier);

        if ((int) $contact->customer_id !== $customerId) {
            throw new ApiException('Owner contact must belong to the same customer.', 422);
        }

        return $contact->id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'ownership_type',
            'status',
            'activated_at',
            'expires_at',
            'notes',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        if (array_key_exists('notes', $payload) && blank($payload['notes'])) {
            $payload['notes'] = null;
        }

        if (! $isUpdate && empty($payload['ownership_type'])) {
            $payload['ownership_type'] = CustomerApplicationOwnershipType::CustomerOwned->value;
        }

        if (! $isUpdate && empty($payload['status'])) {
            $payload['status'] = CustomerApplicationStatus::Pending->value;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeActivationDates(array $payload, ?CustomerApplication $existing = null): array
    {
        if (array_key_exists('activated_at', $payload)) {
            $payload['activated_at'] = blank($payload['activated_at'])
                ? null
                : Carbon::parse((string) $payload['activated_at']);
        }

        if (array_key_exists('expires_at', $payload)) {
            $payload['expires_at'] = blank($payload['expires_at'])
                ? null
                : Carbon::parse((string) $payload['expires_at']);
        }

        $status = $payload['status'] ?? $existing?->status?->value ?? $existing?->status;
        if ($status === CustomerApplicationStatus::Active->value
            && ! array_key_exists('activated_at', $payload)
            && blank($existing?->activated_at)
        ) {
            $payload['activated_at'] = now();
        }

        $activatedAt = $payload['activated_at'] ?? $existing?->activated_at;
        $expiresAt = $payload['expires_at'] ?? $existing?->expires_at;

        if ($activatedAt && $expiresAt && Carbon::parse($expiresAt)->lt(Carbon::parse($activatedAt))) {
            throw new ApiException('Expiration date must be on or after the activation date.', 422);
        }

        return $payload;
    }
}
