<?php

namespace App\Domains\Customers\Services;

use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Repositories\ApplicationEnvironmentRepository;
use App\Domains\Applications\Repositories\ApplicationReleaseRepository;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Domains\Applications\Repositories\ApplicationVersionRepository;
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
use App\Domains\Customers\Repositories\LicenseRepository;
use App\Domains\Customers\Repositories\SubscriptionRepository;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Domains\Support\Repositories\SupportSlaPolicyRepository;
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
        private readonly ApplicationVersionRepository $applicationVersionRepository,
        private readonly ApplicationReleaseRepository $applicationReleaseRepository,
        private readonly IntegrationRepository $integrationRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly LicenseRepository $licenseRepository,
        private readonly SupportSlaPolicyRepository $supportSlaPolicyRepository,
    ) {}

    /**
     * @param array<string, mixed> $filters
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
     * @param array<string, mixed> $filters
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

        return $assignment->load($this->customerApplicationRepository->assignmentRelations());
    }

    /**
     * @param array<string, mixed> $data
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
            $payload = array_merge($payload, $this->resolveTraceability($application->id, $customer->company_id, $data, $application->platform?->value ?? $application->platform));
            $payload['ownership_type'] = $payload['ownership_type']
                ?? CustomerApplicationOwnershipType::CustomerOwned->value;
            $payload['status'] = $payload['status'] ?? CustomerApplicationStatus::Pending->value;
            $payload = $this->normalizeActivationDates($payload);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $assignment = $this->customerApplicationRepository->createAssignment($payload);
            $this->linkCommercialRecords($assignment, $data);
            $assignment->load($this->customerApplicationRepository->assignmentRelations());
            event(new CustomerApplicationAssigned($assignment, $actor));

            return $assignment;
        });
    }

    /**
     * @param array<string, mixed> $data
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

            $assignment->loadMissing('application', 'customer');
            $traceability = $this->resolveTraceability(
                $assignment->application_id,
                (int) $assignment->customer->company_id,
                $data,
                $assignment->application?->platform?->value ?? $assignment->application?->platform,
                partial: true
            );
            $payload = array_merge($payload, $traceability);

            $payload = $this->normalizeActivationDates($payload, $assignment);
            $updated = $this->customerApplicationRepository->updateAssignment($assignment, $payload);
            $this->linkCommercialRecords($updated, $data);
            $updated->load($this->customerApplicationRepository->assignmentRelations());
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
     * @param array<string, mixed> $filters
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
     * @param array<string, mixed> $data
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
            'platform',
            'build_label',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        if (array_key_exists('notes', $payload) && blank($payload['notes'])) {
            $payload['notes'] = null;
        }

        if (array_key_exists('build_label', $payload) && blank($payload['build_label'])) {
            $payload['build_label'] = null;
        }

        if (array_key_exists('platform', $payload) && blank($payload['platform'])) {
            unset($payload['platform']);
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
     * @param array<string, mixed> $payload
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function resolveTraceability(
        int $applicationId,
        int $companyId,
        array $data,
        mixed $applicationPlatform,
        bool $partial = false
    ): array {
        $resolved = [];

        if (! $partial || array_key_exists('application_version_id', $data) || array_key_exists('version_id', $data)) {
            $resolved['application_version_id'] = $this->resolveVersionId(
                $applicationId,
                $data['application_version_id'] ?? $data['version_id'] ?? null
            );
        }

        if (! $partial || array_key_exists('application_release_id', $data) || array_key_exists('release_id', $data)) {
            $versionId = $resolved['application_version_id'] ?? null;
            $resolved['application_release_id'] = $this->resolveReleaseId(
                $applicationId,
                $data['application_release_id'] ?? $data['release_id'] ?? null,
                $versionId
            );
        }

        if (! $partial || array_key_exists('support_sla_policy_id', $data) || array_key_exists('sla_policy_id', $data)) {
            $resolved['support_sla_policy_id'] = $this->resolveSlaPolicyId(
                $companyId,
                $data['support_sla_policy_id'] ?? $data['sla_policy_id'] ?? null
            );
        }

        if (! $partial || array_key_exists('platform', $data)) {
            $platform = $data['platform'] ?? null;

            if (blank($platform)) {
                $platform = $applicationPlatform;
            }

            if (filled($platform) && ! in_array((string) $platform, ApplicationPlatform::values(), true)) {
                throw new ApiException('Platform must be a supported application platform.', 422);
            }

            $resolved['platform'] = filled($platform) ? (string) $platform : null;
        }

        if (
            blank($data['build_label'] ?? null)
            && isset($resolved['application_version_id'])
            && $resolved['application_version_id']
        ) {
            $version = $this->applicationVersionRepository->findByIdentifier((string) $resolved['application_version_id']);
            if ($version?->build_number && ! $partial) {
                $resolved['build_label'] = $version->build_number;
            }
        }

        return $resolved;
    }

    protected function resolveVersionId(int $applicationId, mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        $version = $this->applicationVersionRepository->findForApplication($applicationId, (string) $identifier);

        return $version->id;
    }

    protected function resolveReleaseId(int $applicationId, mixed $identifier, ?int $versionId): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        $release = $this->applicationReleaseRepository->findForApplication($applicationId, (string) $identifier);

        if ($versionId !== null && (int) $release->application_version_id !== $versionId) {
            throw new ApiException('Release must belong to the selected version.', 422);
        }

        return $release->id;
    }

    protected function resolveSlaPolicyId(int $companyId, mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        $policy = $this->supportSlaPolicyRepository->findByIdentifierOrFail((string) $identifier);

        if ($policy->company_id !== null && (int) $policy->company_id !== $companyId) {
            throw new ApiException('SLA policy must belong to the customer company.', 422);
        }

        return $policy->id;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function linkCommercialRecords(CustomerApplication $assignment, array $data): void
    {
        if (array_key_exists('subscription_id', $data)) {
            if (blank($data['subscription_id'])) {
                $this->subscriptionRepository->detachFromAssignment($assignment->id);
            } else {
                $subscription = $this->subscriptionRepository->findByIdentifierOrFail((string) $data['subscription_id']);

                if ((int) $subscription->customer_id !== (int) $assignment->customer_id) {
                    throw new ApiException('Subscription must belong to the same customer.', 422);
                }

                $this->subscriptionRepository->detachFromAssignment($assignment->id);
                $this->subscriptionRepository->linkToAssignment($subscription->id, $assignment->id);
            }
        }

        if (array_key_exists('license_id', $data)) {
            if (blank($data['license_id'])) {
                $this->licenseRepository->detachFromAssignment($assignment->id);
            } else {
                $license = $this->licenseRepository->findByIdentifierOrFail((string) $data['license_id']);

                if ((int) $license->customer_id !== (int) $assignment->customer_id) {
                    throw new ApiException('License must belong to the same customer.', 422);
                }

                $this->licenseRepository->detachFromAssignment($assignment->id);
                $this->licenseRepository->linkToAssignment($license->id, $assignment->id);
            }
        }
    }
}
