<?php

namespace App\Domains\Customers\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Companies\Repositories\LocationRepository;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Events\CustomerCreated;
use App\Domains\Customers\Events\CustomerDeleted;
use App\Domains\Customers\Events\CustomerRestored;
use App\Domains\Customers\Events\CustomerUpdated;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerDocument;
use App\Domains\Customers\Models\Industry;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Domains\Customers\Repositories\IndustryRepository;
use App\Domains\Settings\Support\ApplicationTimezone;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Support\PhoneNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly LocationRepository $locationRepository,
        private readonly IndustryRepository $industryRepository,
        private readonly CustomerApplicationService $customerApplicationService,
    ) {}

    /**
     * @param array<string, mixed> $filters
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
            'company:id,uuid,company_name,status,country,timezone',
            'location:id,uuid,branch_name,city,country,status,company_id',
            'industryMaster:id,uuid,code,name,is_other',
            'subIndustry:id,uuid,code,name,parent_id',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($data, $actor): Customer {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $application = is_array($data['application'] ?? null) ? $data['application'] : null;
            $payload = $this->preparePayload($data);
            $payload = $this->applyIndustry($payload, $data);
            $payload['company_id'] = $company->id;
            $payload['location_id'] = $this->resolveLocationId($data['location_id'] ?? null, $company->id);
            $payload['status'] = $payload['status'] ?? CustomerStatus::Active->value;
            $payload['country'] = $payload['country'] ?? $company->country;
            $payload['timezone'] = $payload['timezone'] ?? ($company->timezone ?: ApplicationTimezone::name());
            $payload['language'] = $payload['language'] ?? 'en';
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $customer = $this->customerRepository->createCustomer($payload);

            if (is_array($application) && filled($application['application_id'] ?? null)) {
                $this->customerApplicationService->create([
                    'customer_id' => $customer->uuid,
                    'application_id' => $application['application_id'],
                    'application_environment_id' => $application['application_environment_id'] ?? $application['environment_id'] ?? null,
                    'ownership_type' => $application['ownership_type'] ?? null,
                    'platform' => $application['platform'] ?? null,
                    'status' => $application['status'] ?? null,
                    'notes' => $application['notes'] ?? null,
                ], $actor);
            }

            event(new CustomerCreated($customer, $actor));

            return $this->show($customer->uuid);
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $identifier, array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Customer {
            $customer = $this->customerRepository->findByIdentifierOrFail($identifier);

            if ($customer->anonymized_at !== null) {
                throw new ApiException('Anonymized customers cannot be edited.', 422);
            }

            $payload = $this->preparePayload($data, isUpdate: true);
            $payload = $this->applyIndustry($payload, $data, $customer);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('company_id', $data) && ! blank($data['company_id'])) {
                $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
                $payload['company_id'] = $company->id;
            }

            if (array_key_exists('location_id', $data)) {
                $companyId = (int) ($payload['company_id'] ?? $customer->company_id);
                $payload['location_id'] = $this->resolveLocationId($data['location_id'], $companyId);
            }

            $updated = $this->customerRepository->updateCustomer($customer, $payload);
            event(new CustomerUpdated($updated, $actor));

            return $this->show($updated->uuid);
        });
    }

    public function anonymize(string $identifier, User $actor): Customer
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($identifier);

        return $this->anonymizeCustomer($customer, $actor);
    }

    public function anonymizeCustomer(Customer $customer, ?User $actor): Customer
    {
        return DB::transaction(function () use ($customer, $actor): Customer {
            if ($customer->anonymized_at !== null) {
                throw new ApiException('Customer is already anonymized.', 422);
            }

            activity()->disableLogging();

            try {
                $this->scrubCustomerPersonalData($customer, $actor);
            } finally {
                activity()->enableLogging();
            }

            $customer = $customer->trashed()
                ? $this->customerRepository->findByIdentifierOrFail($customer->uuid, withTrashed: true)
                : $this->show($customer->uuid);
            $logger = activity()
                ->performedOn($customer)
                ->withProperties([
                    'customer_number' => $customer->customer_number,
                    'anonymized_at' => $customer->anonymized_at?->toIso8601String(),
                ])
                ->event('anonymized');

            if ($actor !== null) {
                $logger->causedBy($actor);
            }

            $logger->log('Customer personal data anonymized');

            if ($actor !== null) {
                event(new CustomerUpdated($customer, $actor));
            }

            return $customer;
        });
    }

    public function enforceRetention(int $limit = 200): int
    {
        $due = Customer::query()
            ->withTrashed()
            ->whereNull('anonymized_at')
            ->whereNotNull('retention_until')
            ->whereDate('retention_until', '<', now()->toDateString())
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        foreach ($due as $customer) {
            $this->anonymizeCustomer($customer, null);
        }

        return $due->count();
    }

    private function scrubCustomerPersonalData(Customer $customer, ?User $actor): void
    {
        $actorId = $actor?->id;

        $customer->contacts()->withTrashed()->update([
            'name' => 'Anonymized contact',
            'email' => null,
            'phone' => null,
            'position' => null,
            'department' => null,
            'responsibilities' => null,
            'notes' => null,
            'updated_by' => $actorId,
        ]);

        $customer->notes()->withTrashed()->update([
            'title' => 'Anonymized note',
            'body' => '',
            'updated_by' => $actorId,
        ]);

        $customer->tasks()->withTrashed()->update([
            'title' => 'Anonymized task',
            'description' => null,
            'updated_by' => $actorId,
        ]);

        $customer->communications()->withTrashed()->update([
            'subject' => 'Anonymized communication',
            'body' => null,
            'channel_reference' => null,
            'participants' => null,
            'updated_by' => $actorId,
        ]);

        $customer->applications()->withTrashed()->update([
            'notes' => null,
            'updated_by' => $actorId,
        ]);

        $customer->subscriptions()->withTrashed()->update([
            'notes' => null,
            'external_subscription_id' => null,
            'external_customer_id' => null,
            'updated_by' => $actorId,
        ]);

        $customer->licenses()->withTrashed()->update([
            'notes' => null,
            'revoked_reason' => null,
            'updated_by' => $actorId,
        ]);

        $customer->analyticsSnapshots()->update([
            'metrics' => null,
        ]);

        $customer->documents()->withTrashed()->each(function (CustomerDocument $document) use ($actorId): void {
            if (filled($document->path) && $document->path !== 'redacted') {
                Storage::disk($document->disk ?: 'public')->delete($document->path);
            }

            $document->forceFill([
                'name' => 'Anonymized document',
                'original_filename' => 'redacted',
                'path' => 'redacted',
                'notes' => null,
                'updated_by' => $actorId,
            ])->saveQuietly();
        });

        $this->customerRepository->updateCustomer($customer, [
            'first_name' => null,
            'last_name' => null,
            'company_name' => $customer->customer_type?->isOrganization() ? 'Anonymized organization' : null,
            'legal_name' => null,
            'registration_number' => null,
            'reference' => null,
            'email' => 'anonymized-'.$customer->id.'@invalid.example',
            'phone' => null,
            'primary_contact_name' => null,
            'primary_contact_email' => null,
            'primary_contact_phone' => null,
            'primary_contact_title' => null,
            'website' => null,
            'industry_other' => null,
            'notes' => null,
            'status' => CustomerStatus::Inactive->value,
            'anonymized_at' => now(),
            'updated_by' => $actorId,
        ]);
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
     * @param array<string, mixed> $filters
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

    private function resolveLocationId(mixed $identifier, int $companyId): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        $location = $this->locationRepository->findByIdentifier((string) $identifier);

        if (! $location || (int) $location->company_id !== $companyId) {
            throw new ApiException('Select an active location for this company.', 422);
        }

        return $location->id;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'customer_type',
            'reference',
            'first_name',
            'last_name',
            'company_name',
            'legal_name',
            'registration_number',
            'email',
            'phone',
            'primary_contact_name',
            'primary_contact_email',
            'primary_contact_phone',
            'primary_contact_title',
            'website',
            'industry',
            'industry_other',
            'country',
            'timezone',
            'language',
            'legal_basis',
            'processing_purpose',
            'retention_until',
            'status',
            'notes',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach ([
            'reference',
            'first_name',
            'last_name',
            'company_name',
            'legal_name',
            'registration_number',
            'phone',
            'primary_contact_name',
            'primary_contact_email',
            'primary_contact_phone',
            'primary_contact_title',
            'website',
            'industry',
            'industry_other',
            'country',
            'notes',
            'legal_basis',
            'processing_purpose',
            'retention_until',
        ] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        foreach (['phone', 'primary_contact_phone'] as $phoneField) {
            if (array_key_exists($phoneField, $payload) && $payload[$phoneField] !== null) {
                $payload[$phoneField] = PhoneNumber::store($payload[$phoneField]);
            }
        }

        foreach (['email', 'primary_contact_email'] as $emailField) {
            if (array_key_exists($emailField, $payload) && is_string($payload[$emailField])) {
                $payload[$emailField] = strtolower(trim($payload[$emailField]));
            }
        }

        if ($isUpdate && array_key_exists('timezone', $payload) && blank($payload['timezone'])) {
            unset($payload['timezone']);
        }

        if (! $isUpdate && empty($payload['language'])) {
            $payload['language'] = 'en';
        }

        if (isset($payload['customer_type'])) {
            $type = $payload['customer_type'] instanceof CustomerType
                ? $payload['customer_type']
                : CustomerType::tryFrom((string) $payload['customer_type']);

            if ($type?->requiresPersonName()) {
                $payload['company_name'] = null;
                $payload['legal_name'] = null;
                $payload['registration_number'] = null;
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function applyIndustry(array $payload, array $data, ?Customer $existing = null): array
    {
        $industrySelected = array_key_exists('industry_id', $data) || array_key_exists('sub_industry_id', $data);

        if (! $industrySelected && $existing === null) {
            return $payload;
        }

        if (! $industrySelected) {
            return $payload;
        }

        $industry = $this->resolveIndustry($data['industry_id'] ?? null);
        $subIndustry = $this->resolveIndustry($data['sub_industry_id'] ?? null);

        if ($subIndustry && $industry && (int) $subIndustry->parent_id !== (int) $industry->id) {
            throw new ApiException('Sub-industry must belong to the selected industry.', 422);
        }

        if ($subIndustry && ! $industry) {
            throw new ApiException('Select an industry before choosing a sub-industry.', 422);
        }

        $payload['industry_id'] = $industry?->id;
        $payload['sub_industry_id'] = $subIndustry?->id;

        if ($industry?->is_other) {
            $payload['industry'] = $payload['industry_other'] ?? $existing?->industry_other;
            $payload['sub_industry_id'] = null;
        } elseif ($subIndustry) {
            $payload['industry'] = $industry->name . ' / ' . $subIndustry->name;
            $payload['industry_other'] = null;
        } elseif ($industry) {
            $payload['industry'] = $industry->name;
            $payload['industry_other'] = null;
        }

        return $payload;
    }

    protected function resolveIndustry(mixed $identifier): ?Industry
    {
        if (blank($identifier)) {
            return null;
        }

        $industry = $this->industryRepository->findByIdentifier((string) $identifier);

        if (! $industry || ! $industry->is_active) {
            throw new ApiException('Selected industry is not available.', 422);
        }

        return $industry;
    }
}
