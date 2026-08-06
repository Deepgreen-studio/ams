<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Contracts\SubscriptionBillingGatewayInterface;
use App\Domains\Customers\Enums\LicenseStatus;
use App\Domains\Customers\Enums\PaymentProvider;
use App\Domains\Customers\Enums\PaymentStatus;
use App\Domains\Customers\Enums\SubscriptionPlanType;
use App\Domains\Customers\Enums\SubscriptionStatus;
use App\Domains\Customers\Events\LicenseCreated;
use App\Domains\Customers\Events\SubscriptionCancelled;
use App\Domains\Customers\Events\SubscriptionCreated;
use App\Domains\Customers\Events\SubscriptionDeleted;
use App\Domains\Customers\Events\SubscriptionRestored;
use App\Domains\Customers\Events\SubscriptionUpdated;
use App\Domains\Customers\Models\License;
use App\Domains\Customers\Models\Subscription;
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

class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly LicenseRepository $licenseRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerApplicationRepository $customerApplicationRepository,
        private readonly SubscriptionBillingGatewayInterface $billingGateway
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{subscriptions: LengthAwarePaginator, statistics: array<string, int>, renewal_reminders: Collection<int, Subscription>}
     */
    public function dashboard(array $filters = []): array
    {
        $filters = $this->resolveFilters($filters);
        $customerId = isset($filters['customer_id']) ? (int) $filters['customer_id'] : null;

        return [
            'subscriptions' => $this->subscriptionRepository->paginateFiltered($filters),
            'statistics' => $this->subscriptionRepository->statistics($customerId),
            'renewal_reminders' => $this->subscriptionRepository->renewalReminders($customerId),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->subscriptionRepository->paginateFiltered($this->resolveFilters($filters));
    }

    public function find(string $identifier, bool $withTrashed = false): Subscription
    {
        return $this->subscriptionRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): Subscription
    {
        return $this->find($identifier)->load([
            'customer:id,uuid,first_name,last_name,company_name,email,customer_type,status,company_id',
            'customerApplication:id,uuid,application_id,status,ownership_type',
            'customerApplication.application:id,uuid,name,slug,platform,status',
            'licenses',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ])->loadCount('licenses');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, bool $issueLicense = true): Subscription
    {
        return DB::transaction(function () use ($data, $actor, $issueLicense): Subscription {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $data['customer_id']);
            $payload = $this->preparePayload($data);
            $payload['customer_id'] = $customer->id;
            $payload['customer_application_id'] = $this->resolveAssignmentId(
                $customer->id,
                $data['customer_application_id'] ?? null
            );

            $planType = SubscriptionPlanType::from((string) $payload['plan_type']);
            $payload['plan_name'] = $payload['plan_name'] ?? $planType->label().' Plan';
            $payload['status'] = $payload['status'] ?? ($planType === SubscriptionPlanType::Trial
                ? SubscriptionStatus::Trialing->value
                : SubscriptionStatus::Active->value);
            $payload['payment_status'] = $payload['payment_status'] ?? $planType->defaultPaymentStatus()->value;
            $payload['payment_provider'] = $payload['payment_provider'] ?? $this->billingGateway->provider();
            $payload['currency'] = $payload['currency'] ?? config('billing.currency', 'USD');
            $payload['renewal_reminder_days'] = $payload['renewal_reminder_days']
                ?? (int) config('billing.renewal_reminder_days', 14);
            $payload = $this->applyPlanDateDefaults($payload, $planType);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $subscription = $this->subscriptionRepository->createSubscription($payload);

            $billing = $this->billingGateway->createSubscription($subscription);
            if ($billing->success) {
                $subscription = $this->subscriptionRepository->updateSubscription($subscription, array_filter([
                    'external_subscription_id' => $billing->externalSubscriptionId,
                    'external_customer_id' => $billing->externalCustomerId,
                    'payment_status' => $billing->paymentStatus,
                    'payment_provider' => $this->billingGateway->provider(),
                ], static fn ($value) => $value !== null));
            }

            if ($issueLicense) {
                $license = $this->licenseRepository->createLicense([
                    'subscription_id' => $subscription->id,
                    'customer_id' => $subscription->customer_id,
                    'customer_application_id' => $subscription->customer_application_id,
                    'license_key' => License::generateLicenseKey(),
                    'status' => LicenseStatus::Active->value,
                    'starts_at' => $subscription->starts_at,
                    'expires_at' => $subscription->expires_at,
                    'features' => $subscription->features,
                    'max_activations' => $data['max_activations'] ?? 5,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
                event(new LicenseCreated($license, $actor));
            }

            $subscription = $subscription->fresh(['licenses', 'customer', 'customerApplication.application']) ?? $subscription;
            event(new SubscriptionCreated($subscription, $actor));

            return $subscription;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Subscription
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Subscription {
            $subscription = $this->subscriptionRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('customer_application_id', $data)) {
                $payload['customer_application_id'] = $this->resolveAssignmentId(
                    $subscription->customer_id,
                    $data['customer_application_id']
                );
            }

            $payload = $this->normalizeDates($payload);
            $updated = $this->subscriptionRepository->updateSubscription($subscription, $payload);
            event(new SubscriptionUpdated($updated, $actor));

            return $updated;
        });
    }

    public function cancel(string $identifier, User $actor, ?string $reason = null): Subscription
    {
        return DB::transaction(function () use ($identifier, $actor, $reason): Subscription {
            $subscription = $this->subscriptionRepository->findByIdentifierOrFail($identifier);

            $this->billingGateway->cancelSubscription($subscription, ['reason' => $reason]);

            $updated = $this->subscriptionRepository->updateSubscription($subscription, [
                'status' => SubscriptionStatus::Cancelled->value,
                'cancelled_at' => now(),
                'notes' => trim(($subscription->notes ? $subscription->notes."\n" : '').($reason ? 'Cancelled: '.$reason : 'Cancelled')),
                'updated_by' => $actor->id,
            ]);

            event(new SubscriptionCancelled($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $subscription = $this->subscriptionRepository->findByIdentifierOrFail($identifier);
            $this->subscriptionRepository->updateSubscription($subscription, ['updated_by' => $actor->id]);
            $subscription->delete();
            event(new SubscriptionDeleted($subscription, $actor));
        });
    }

    public function restore(string $identifier, User $actor): Subscription
    {
        return DB::transaction(function () use ($identifier, $actor): Subscription {
            $subscription = $this->subscriptionRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $subscription->trashed()) {
                throw new ApiException('Subscription is not archived.', 422);
            }

            $subscription->restore();
            $restored = $this->subscriptionRepository->updateSubscription($subscription, ['updated_by' => $actor->id]);
            event(new SubscriptionRestored($restored, $actor));

            return $restored;
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(string $identifier, int $limit = 50): Collection
    {
        return $this->subscriptionRepository->timeline($this->find($identifier), $limit);
    }

    /**
     * @return array<string, int>
     */
    public function statistics(?string $customerIdentifier = null): array
    {
        $customerId = null;
        if (! blank($customerIdentifier)) {
            $customerId = $this->customerRepository->findByIdentifierOrFail($customerIdentifier)->id;
        }

        return $this->subscriptionRepository->statistics($customerId);
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
            'plan_type',
            'plan_name',
            'status',
            'starts_at',
            'expires_at',
            'renews_at',
            'trial_ends_at',
            'features',
            'payment_status',
            'payment_provider',
            'currency',
            'amount',
            'renewal_reminder_days',
            'notes',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        if (array_key_exists('notes', $payload) && blank($payload['notes'])) {
            $payload['notes'] = null;
        }

        if (array_key_exists('features', $payload)) {
            if (is_string($payload['features'])) {
                $decoded = json_decode($payload['features'], true);
                $payload['features'] = is_array($decoded) ? $decoded : array_values(array_filter(array_map('trim', explode(',', $payload['features']))));
            } elseif (! is_array($payload['features'])) {
                $payload['features'] = null;
            }
        }

        if (! $isUpdate && empty($payload['plan_type'])) {
            $payload['plan_type'] = SubscriptionPlanType::Monthly->value;
        }

        if (array_key_exists('payment_provider', $payload) && blank($payload['payment_provider'])) {
            $payload['payment_provider'] = PaymentProvider::Manual->value;
        }

        return $this->normalizeDates($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeDates(array $payload): array
    {
        foreach (['starts_at', 'expires_at', 'renews_at', 'trial_ends_at'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = blank($payload[$field]) ? null : Carbon::parse((string) $payload[$field]);
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function applyPlanDateDefaults(array $payload, SubscriptionPlanType $planType): array
    {
        $startsAt = isset($payload['starts_at'])
            ? Carbon::parse($payload['starts_at'])
            : now();
        $payload['starts_at'] = $startsAt;

        if (! array_key_exists('expires_at', $payload) || blank($payload['expires_at'] ?? null)) {
            $payload['expires_at'] = match ($planType) {
                SubscriptionPlanType::Trial => $startsAt->copy()->addDays(14),
                SubscriptionPlanType::Monthly => $startsAt->copy()->addMonth(),
                SubscriptionPlanType::Yearly, SubscriptionPlanType::Enterprise => $startsAt->copy()->addYear(),
                SubscriptionPlanType::Lifetime => null,
            };
        }

        if (! array_key_exists('renews_at', $payload) || blank($payload['renews_at'] ?? null)) {
            $payload['renews_at'] = match ($planType) {
                SubscriptionPlanType::Monthly, SubscriptionPlanType::Yearly, SubscriptionPlanType::Enterprise => $payload['expires_at'],
                default => null,
            };
        }

        if ($planType === SubscriptionPlanType::Trial && blank($payload['trial_ends_at'] ?? null)) {
            $payload['trial_ends_at'] = $payload['expires_at'];
        }

        if ($payload['expires_at'] && Carbon::parse($payload['expires_at'])->lt(Carbon::parse($payload['starts_at']))) {
            throw new ApiException('Expiry date must be on or after the start date.', 422);
        }

        return $payload;
    }
}
