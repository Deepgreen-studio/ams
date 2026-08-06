<?php

namespace App\Domains\Compliance\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Compliance\Enums\ConsentHistoryAction;
use App\Domains\Compliance\Enums\ConsentSource;
use App\Domains\Compliance\Enums\ConsentStatus;
use App\Domains\Compliance\Events\ConsentGranted;
use App\Domains\Compliance\Events\ConsentUpdated;
use App\Domains\Compliance\Events\ConsentWithdrawn;
use App\Domains\Compliance\Models\ConsentType;
use App\Domains\Compliance\Models\UserConsent;
use App\Domains\Compliance\Repositories\ConsentHistoryRepository;
use App\Domains\Compliance\Repositories\ConsentTypeRepository;
use App\Domains\Compliance\Repositories\UserConsentRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsentService
{
    public function __construct(
        private readonly ConsentTypeRepository $consentTypeRepository,
        private readonly UserConsentRepository $userConsentRepository,
        private readonly ConsentHistoryRepository $consentHistoryRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly CustomerRepository $customerRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $companyIdentifier = null): array
    {
        $companyId = $this->resolveCompanyId($companyIdentifier);

        return [
            'statistics' => $this->userConsentRepository->statistics($companyId),
            'recent' => $this->userConsentRepository->recent($companyId),
            'types' => $this->consentTypeRepository->listActive([
                'company_id' => $companyId,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listTypes(array $filters = []): LengthAwarePaginator|Collection
    {
        $companyId = $this->resolveCompanyId($filters['company'] ?? $filters['company_id'] ?? null);
        if ($companyId !== null) {
            $filters['company_id'] = $companyId;
        }

        if (($filters['all'] ?? null) === true || ($filters['all'] ?? null) === '1') {
            return $this->consentTypeRepository->listActive($filters);
        }

        return $this->consentTypeRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listConsents(array $filters = []): LengthAwarePaginator
    {
        $companyId = $this->resolveCompanyId($filters['company'] ?? $filters['company_id'] ?? null);
        if ($companyId !== null) {
            $filters['company_id'] = $companyId;
        }

        if (! empty($filters['consent_type']) && empty($filters['consent_type_id'])) {
            $type = $this->consentTypeRepository->findByIdentifierOrFail((string) $filters['consent_type']);
            $filters['consent_type_id'] = $type->id;
        }

        if (! empty($filters['user']) && empty($filters['user_id'])) {
            $user = $this->resolveUser($filters['user']);
            $filters['user_id'] = $user?->id;
        }

        if (! empty($filters['customer']) && empty($filters['customer_id'])) {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $filters['customer']);
            $filters['customer_id'] = $customer->id;
            if ($companyId === null) {
                $filters['company_id'] = $customer->company_id;
            }
        }

        return $this->userConsentRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listHistory(array $filters = []): LengthAwarePaginator
    {
        $companyId = $this->resolveCompanyId($filters['company'] ?? $filters['company_id'] ?? null);
        if ($companyId !== null) {
            $filters['company_id'] = $companyId;
        }

        if (! empty($filters['consent']) && empty($filters['user_consent_id'])) {
            $consent = $this->userConsentRepository->findByIdentifierOrFail((string) $filters['consent']);
            $filters['user_consent_id'] = $consent->id;
        }

        return $this->consentHistoryRepository->paginateFiltered($filters);
    }

    public function show(string $identifier): UserConsent
    {
        return $this->userConsentRepository->findByIdentifierOrFail($identifier)->load([
            'company:id,uuid,company_name,status',
            'consentType',
            'user:id,uuid,full_name,email',
            'customer:id,uuid,first_name,last_name,company_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @return Collection<int, \App\Domains\Compliance\Models\ConsentHistory>
     */
    public function timeline(string $identifier): Collection
    {
        $consent = $this->userConsentRepository->findByIdentifierOrFail($identifier);

        return $this->consentHistoryRepository->forConsent($consent->id);
    }

    /**
     * Preference center: active types + current consent state for a subject.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function preferenceCenter(array $filters): array
    {
        $company = $this->companyRepository->findByIdentifierOrFail((string) ($filters['company_id'] ?? ''));
        $user = ! empty($filters['user_id']) ? $this->resolveUser($filters['user_id']) : null;
        $customer = ! empty($filters['customer_id'])
            ? $this->resolveCustomer($filters['customer_id'], $company->id)
            : null;
        $email = isset($filters['subject_email']) ? strtolower((string) $filters['subject_email']) : null;

        if ($user === null && $customer === null && blank($email)) {
            throw new ApiException('Provide user_id, customer_id, or subject_email for preference center.', 422);
        }

        $types = $this->consentTypeRepository->listActive(['company_id' => $company->id]);
        $existing = $this->userConsentRepository->forSubjectPreferences(
            $company->id,
            $user?->id,
            $customer?->id,
            $email
        )->keyBy('consent_type_id');

        $preferences = $types->map(function (ConsentType $type) use ($existing) {
            /** @var UserConsent|null $consent */
            $consent = $existing->get($type->id);

            return [
                'consent_type' => $type,
                'consent' => $consent,
                'granted' => $consent?->granted ?? false,
                'status' => $consent?->status?->value ?? ConsentStatus::Pending->value,
                'consent_version' => $consent?->consent_version ?? $type->current_version,
                'consented_at' => $consent?->consented_at,
                'withdrawn_at' => $consent?->withdrawn_at,
            ];
        });

        return [
            'company' => $company,
            'subject' => [
                'user' => $user,
                'customer' => $customer,
                'subject_email' => $email ?? $user?->email ?? $customer?->email,
                'subject_name' => $user?->full_name ?? $customer?->display_name ?? ($filters['subject_name'] ?? null),
            ],
            'preferences' => $preferences,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function grantOrUpdate(array $data, User $actor, ?string $ip = null, ?string $userAgent = null): UserConsent
    {
        return DB::transaction(function () use ($data, $actor, $ip, $userAgent): UserConsent {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $type = $this->consentTypeRepository->resolveForCompany((string) $data['consent_type_id'], $company->id);

            if (! $type->is_active) {
                throw new ApiException('Consent type is inactive.', 422);
            }

            $user = ! empty($data['user_id']) ? $this->resolveUser($data['user_id']) : null;
            $customer = ! empty($data['customer_id'])
                ? $this->resolveCustomer($data['customer_id'], $company->id)
                : null;

            $email = isset($data['subject_email'])
                ? strtolower((string) $data['subject_email'])
                : ($user?->email ?? $customer?->email);
            $name = $data['subject_name'] ?? $user?->full_name ?? $customer?->display_name;

            if ($user === null && $customer === null && blank($email)) {
                throw new ApiException('A subject user, customer, or email is required.', 422);
            }

            $granted = array_key_exists('granted', $data)
                ? (bool) $data['granted']
                : true;

            $status = $granted ? ConsentStatus::Granted : ConsentStatus::Withdrawn;
            $source = ConsentSource::tryFrom((string) ($data['source'] ?? ConsentSource::Admin->value))
                ?? ConsentSource::Admin;
            $version = (string) ($data['consent_version'] ?? $type->current_version);
            $device = $data['device'] ?? $this->detectDevice($userAgent);
            $ipAddress = $data['ip_address'] ?? $ip;

            $existing = $this->userConsentRepository->findActiveForSubject(
                $company->id,
                $type->id,
                $user?->id,
                $customer?->id,
                $email
            );

            $context = [
                'ip_address' => $ipAddress,
                'device' => $device,
                'source' => $source->value,
            ];

            if ($existing) {
                return $this->applyConsentChange(
                    $existing,
                    $granted,
                    $status,
                    $version,
                    $source,
                    $context,
                    $userAgent,
                    $data['notes'] ?? null,
                    $actor
                );
            }

            $payload = [
                'company_id' => $company->id,
                'consent_type_id' => $type->id,
                'user_id' => $user?->id,
                'customer_id' => $customer?->id,
                'subject_email' => $email,
                'subject_name' => $name,
                'consent_version' => $version,
                'status' => $status->value,
                'granted' => $granted,
                'consented_at' => $granted ? now() : null,
                'withdrawn_at' => $granted ? null : now(),
                'ip_address' => $ipAddress,
                'device' => $device,
                'user_agent' => $userAgent,
                'source' => $source->value,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ];

            $consent = $this->userConsentRepository->createConsent($payload);

            $this->consentHistoryRepository->recordForConsent(
                $consent,
                $granted ? ConsentHistoryAction::Granted->value : ConsentHistoryAction::Created->value,
                null,
                $status->value,
                null,
                $granted,
                null,
                $version,
                $actor->id,
                $granted ? 'Consent granted' : 'Consent recorded as withdrawn',
                $context
            );

            if ($granted) {
                event(new ConsentGranted($consent, $actor));
            } else {
                event(new ConsentWithdrawn($consent, $actor));
            }

            return $consent;
        });
    }

    /**
     * Bulk preference center save.
     *
     * @param  array<string, mixed>  $data
     * @return list<UserConsent>
     */
    public function savePreferences(array $data, User $actor, ?string $ip = null, ?string $userAgent = null): array
    {
        $preferences = $data['preferences'] ?? [];
        if (! is_array($preferences) || $preferences === []) {
            throw new ApiException('At least one preference is required.', 422);
        }

        $results = [];

        foreach ($preferences as $preference) {
            if (! is_array($preference)) {
                continue;
            }

            $payload = [
                'company_id' => $data['company_id'],
                'consent_type_id' => $preference['consent_type_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'subject_email' => $data['subject_email'] ?? null,
                'subject_name' => $data['subject_name'] ?? null,
                'granted' => (bool) ($preference['granted'] ?? false),
                'source' => $data['source'] ?? ConsentSource::PreferenceCenter->value,
                'device' => $data['device'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'consent_version' => $preference['consent_version'] ?? null,
                'notes' => $preference['notes'] ?? null,
            ];

            $results[] = $this->grantOrUpdate($payload, $actor, $ip, $userAgent);
        }

        return $results;
    }

    public function withdraw(string $identifier, array $data, User $actor, ?string $ip = null, ?string $userAgent = null): UserConsent
    {
        return DB::transaction(function () use ($identifier, $data, $actor, $ip, $userAgent): UserConsent {
            $consent = $this->userConsentRepository->findByIdentifierOrFail($identifier);

            $source = ConsentSource::tryFrom((string) ($data['source'] ?? ConsentSource::Admin->value))
                ?? ConsentSource::Admin;

            $context = [
                'ip_address' => $data['ip_address'] ?? $ip ?? $consent->ip_address,
                'device' => $data['device'] ?? $this->detectDevice($userAgent) ?? $consent->device,
                'source' => $source->value,
            ];

            return $this->applyConsentChange(
                $consent,
                false,
                ConsentStatus::Withdrawn,
                $consent->consent_version,
                $source,
                $context,
                $userAgent ?? $consent->user_agent,
                $data['notes'] ?? 'Consent withdrawn',
                $actor
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createType(array $data, User $actor): ConsentType
    {
        return DB::transaction(function () use ($data, $actor): ConsentType {
            $companyId = null;
            if (! empty($data['company_id'])) {
                $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
                $companyId = $company->id;
            }

            $code = Str::slug((string) ($data['code'] ?? $data['channel'] ?? ''), '_');
            if ($code === '') {
                throw new ApiException('Consent type code is required.', 422);
            }

            return $this->consentTypeRepository->createType([
                'company_id' => $companyId,
                'code' => $code,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'channel' => $data['channel'],
                'current_version' => $data['current_version'] ?? '1.0',
                'is_required' => (bool) ($data['is_required'] ?? false),
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    protected function applyConsentChange(
        UserConsent $consent,
        bool $granted,
        ConsentStatus $status,
        string $version,
        ConsentSource $source,
        array $context,
        ?string $userAgent,
        ?string $notes,
        User $actor
    ): UserConsent {
        $fromStatus = $consent->status?->value ?? (string) $consent->status;
        $fromGranted = (bool) $consent->granted;
        $fromVersion = $consent->consent_version;

        $payload = [
            'status' => $status->value,
            'granted' => $granted,
            'consent_version' => $version,
            'source' => $source->value,
            'ip_address' => $context['ip_address'] ?? $consent->ip_address,
            'device' => $context['device'] ?? $consent->device,
            'user_agent' => $userAgent ?? $consent->user_agent,
            'notes' => $notes ?? $consent->notes,
            'updated_by' => $actor->id,
        ];

        if ($granted) {
            $payload['consented_at'] = $consent->consented_at ?? now();
            $payload['withdrawn_at'] = null;
        } else {
            $payload['withdrawn_at'] = now();
        }

        $updated = $this->userConsentRepository->updateConsent($consent, $payload);

        $action = ConsentHistoryAction::Updated->value;
        if ($fromGranted !== $granted) {
            $action = $granted
                ? ConsentHistoryAction::Granted->value
                : ConsentHistoryAction::Withdrawn->value;
        } elseif ($fromVersion !== $version) {
            $action = ConsentHistoryAction::VersionChanged->value;
        }

        $this->consentHistoryRepository->recordForConsent(
            $updated,
            $action,
            $fromStatus,
            $status->value,
            $fromGranted,
            $granted,
            $fromVersion,
            $version,
            $actor->id,
            $notes ?? ($granted ? 'Consent granted' : 'Consent withdrawn'),
            $context
        );

        event(new ConsentUpdated($updated, $actor));

        if ($fromGranted !== $granted) {
            if ($granted) {
                event(new ConsentGranted($updated, $actor));
            } else {
                event(new ConsentWithdrawn($updated, $actor));
            }
        }

        return $updated;
    }

    protected function resolveCompanyId(mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        return $this->companyRepository->findByIdentifierOrFail((string) $identifier)->id;
    }

    protected function resolveUser(mixed $identifier): ?User
    {
        if (blank($identifier)) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit((string) $identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (! $user) {
            throw new ApiException('User not found.', 422);
        }

        return $user;
    }

    protected function resolveCustomer(mixed $identifier, int $companyId): ?\App\Domains\Customers\Models\Customer
    {
        if (blank($identifier)) {
            return null;
        }

        $customer = $this->customerRepository->findByIdentifierOrFail((string) $identifier);

        if ((int) $customer->company_id !== $companyId) {
            throw new ApiException('Customer does not belong to the selected company.', 422);
        }

        return $customer;
    }

    protected function detectDevice(?string $userAgent): ?string
    {
        if (blank($userAgent)) {
            return null;
        }

        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'iphone') => 'iPhone',
            str_contains($ua, 'ipad') => 'iPad',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'windows') => 'Windows Desktop',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'Mac Desktop',
            str_contains($ua, 'linux') => 'Linux Desktop',
            default => 'Unknown Device',
        };
    }
}
