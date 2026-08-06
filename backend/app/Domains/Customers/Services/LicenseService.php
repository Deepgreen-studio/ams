<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Enums\LicenseStatus;
use App\Domains\Customers\Events\LicenseCreated;
use App\Domains\Customers\Events\LicenseDeleted;
use App\Domains\Customers\Events\LicenseRestored;
use App\Domains\Customers\Events\LicenseRevoked;
use App\Domains\Customers\Events\LicenseUpdated;
use App\Domains\Customers\Models\License;
use App\Domains\Customers\Repositories\CustomerApplicationRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Domains\Customers\Repositories\LicenseRepository;
use App\Domains\Customers\Repositories\SubscriptionRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LicenseService
{
    public function __construct(
        private readonly LicenseRepository $licenseRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerApplicationRepository $customerApplicationRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{licenses: LengthAwarePaginator, statistics: array<string, int>}
     */
    public function list(array $filters = []): array
    {
        $filters = $this->resolveFilters($filters);
        $customerId = isset($filters['customer_id']) ? (int) $filters['customer_id'] : null;

        return [
            'licenses' => $this->licenseRepository->paginateFiltered($filters),
            'statistics' => $this->licenseRepository->statistics($customerId),
        ];
    }

    /**
     * History includes soft-deleted (revoked/archived) licenses.
     *
     * @param  array<string, mixed>  $filters
     */
    public function history(array $filters = []): LengthAwarePaginator
    {
        $filters['trashed'] = $filters['trashed'] ?? 'with';
        $filters['sort_by'] = $filters['sort_by'] ?? 'updated_at';

        return $this->licenseRepository->paginateFiltered($this->resolveFilters($filters));
    }

    public function find(string $identifier, bool $withTrashed = false): License
    {
        return $this->licenseRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): License
    {
        return $this->find($identifier)->load([
            'subscription:id,uuid,plan_name,plan_type,status,payment_status,customer_id,starts_at,expires_at,renews_at',
            'customer:id,uuid,first_name,last_name,company_name,email',
            'customerApplication:id,uuid,application_id,status',
            'customerApplication.application:id,uuid,name,slug,platform,status',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): License
    {
        return DB::transaction(function () use ($data, $actor): License {
            $subscription = $this->subscriptionRepository->findByIdentifierOrFail((string) $data['subscription_id']);
            $payload = $this->preparePayload($data);
            $payload['subscription_id'] = $subscription->id;
            $payload['customer_id'] = $subscription->customer_id;
            $payload['customer_application_id'] = $this->resolveAssignmentId(
                $subscription->customer_id,
                $data['customer_application_id'] ?? $subscription->customer_application_id
            );
            $payload['license_key'] = $payload['license_key'] ?? License::generateLicenseKey();
            $payload['status'] = $payload['status'] ?? LicenseStatus::Active->value;
            $payload['starts_at'] = $payload['starts_at'] ?? $subscription->starts_at ?? now();
            $payload['expires_at'] = array_key_exists('expires_at', $payload)
                ? $payload['expires_at']
                : $subscription->expires_at;
            $payload['features'] = $payload['features'] ?? $subscription->features;
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $license = $this->licenseRepository->createLicense($payload);
            event(new LicenseCreated($license, $actor));

            return $license;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): License
    {
        return DB::transaction(function () use ($identifier, $data, $actor): License {
            $license = $this->licenseRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('customer_application_id', $data)) {
                $payload['customer_application_id'] = $this->resolveAssignmentId(
                    $license->customer_id,
                    $data['customer_application_id']
                );
            }

            $updated = $this->licenseRepository->updateLicense($license, $payload);
            event(new LicenseUpdated($updated, $actor));

            return $updated;
        });
    }

    public function revoke(string $identifier, User $actor, ?string $reason = null): License
    {
        return DB::transaction(function () use ($identifier, $actor, $reason): License {
            $license = $this->licenseRepository->findByIdentifierOrFail($identifier);

            $updated = $this->licenseRepository->updateLicense($license, [
                'status' => LicenseStatus::Revoked->value,
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_by' => $actor->id,
            ]);

            event(new LicenseRevoked($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $license = $this->licenseRepository->findByIdentifierOrFail($identifier);
            $this->licenseRepository->updateLicense($license, ['updated_by' => $actor->id]);
            $license->delete();
            event(new LicenseDeleted($license, $actor));
        });
    }

    public function restore(string $identifier, User $actor): License
    {
        return DB::transaction(function () use ($identifier, $actor): License {
            $license = $this->licenseRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $license->trashed()) {
                throw new ApiException('License is not archived.', 422);
            }

            $license->restore();
            $restored = $this->licenseRepository->updateLicense($license, ['updated_by' => $actor->id]);
            event(new LicenseRestored($restored, $actor));

            return $restored;
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(string $identifier, int $limit = 50): Collection
    {
        return $this->licenseRepository->timeline($this->find($identifier), $limit);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function resolveFilters(array $filters): array
    {
        $customerIdentifier = $filters['customer'] ?? $filters['customer_id'] ?? null;
        if (! empty($customerIdentifier) && ! is_numeric($customerIdentifier)) {
            $filters['customer_id'] = $this->customerRepository->findByIdentifierOrFail((string) $customerIdentifier)->id;
        }

        $subscriptionIdentifier = $filters['subscription'] ?? $filters['subscription_id'] ?? null;
        if (! empty($subscriptionIdentifier) && ! is_numeric($subscriptionIdentifier)) {
            $filters['subscription_id'] = $this->subscriptionRepository->findByIdentifierOrFail((string) $subscriptionIdentifier)->id;
        }

        return $filters;
    }

    protected function resolveAssignmentId(int $customerId, mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        $assignment = $this->customerApplicationRepository->findByIdentifierOrFail((string) $identifier);

        if ((int) $assignment->customer_id !== $customerId) {
            throw new ApiException('Application assignment must belong to the same customer.', 422);
        }

        return $assignment->id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'license_key',
            'status',
            'starts_at',
            'expires_at',
            'features',
            'max_activations',
            'notes',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['notes', 'license_key'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = $nullable === 'license_key' ? null : null;
            }
        }

        if (array_key_exists('features', $payload)) {
            if (is_string($payload['features'])) {
                $decoded = json_decode($payload['features'], true);
                $payload['features'] = is_array($decoded)
                    ? $decoded
                    : array_values(array_filter(array_map('trim', explode(',', $payload['features']))));
            } elseif (! is_array($payload['features'])) {
                $payload['features'] = null;
            }
        }

        foreach (['starts_at', 'expires_at'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = blank($payload[$field]) ? null : Carbon::parse((string) $payload[$field]);
            }
        }

        if (! $isUpdate && empty($payload['status'])) {
            $payload['status'] = LicenseStatus::Active->value;
        }

        return $payload;
    }
}
