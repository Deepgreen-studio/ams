<?php

namespace App\Domains\Customers\Services;

use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Companies\Repositories\LocationRepository;
use App\Domains\Customers\Enums\CustomerLegalBasis;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Domains\Customers\Repositories\IndustryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Support\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CustomerImportService
{
    private const MAX_ROWS = 1000;

    private const EXPORT_LIMIT = 5000;

    /**
     * @var array<string, string>
     */
    private const HEADER_ALIASES = [
        'organization_name' => 'legal_name',
        'organisation_name' => 'legal_name',
        'company_name' => 'legal_name',
        'owning_company' => 'company',
        'type' => 'customer_type',
    ];

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerRepository $customerRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly IndustryRepository $industryRepository,
        private readonly LocationRepository $locationRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(array $filters, string $format = 'csv'): StreamedResponse
    {
        $customers = $this->customersForExport($filters);
        $filename = 'customers-'.now()->format('Ymd-His');

        if ($format === 'xlsx') {
            return $this->streamXlsx($this->table($customers), $filename.'.xlsx');
        }

        return $this->streamCsv($this->table($customers), $filename.'.csv');
    }

    public function example(?string $companyIdentifier = null, string $format = 'csv'): StreamedResponse
    {
        $companyName = 'Your Company Name';

        if (filled($companyIdentifier)) {
            $company = $this->companyRepository->findByNameOrIdentifier((string) $companyIdentifier);
            if ($company) {
                $companyName = $company->company_name;
            }
        }

        $rows = [$this->headings()];

        foreach ($this->exampleRecords($companyName) as $record) {
            $rows[] = $this->ordered($record);
        }

        $filename = 'customers-example';

        if ($format === 'xlsx') {
            return $this->streamXlsx($rows, $filename.'.xlsx');
        }

        return $this->streamCsv($rows, $filename.'.csv');
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function import(UploadedFile $file, ?string $defaultCompany, bool $updateExisting, User $actor): array
    {
        $rows = $this->readRows($file);

        if ($rows === []) {
            throw new ApiException('The import file is empty.', 422);
        }

        $header = $this->normalizeHeader(array_shift($rows) ?? []);
        $this->assertRequiredColumns($header, $defaultCompany);

        if (count($rows) > self::MAX_ROWS) {
            throw new ApiException('Import files are limited to '.self::MAX_ROWS.' customers.', 422);
        }

        $fallbackCompany = filled($defaultCompany)
            ? $this->resolveCompany((string) $defaultCompany)
            : null;

        if (filled($defaultCompany) && ! $fallbackCompany) {
            throw new ApiException('The selected company was not found.', 422);
        }

        $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $record = $this->mapRow($header, $row);

            if ($this->rowIsEmpty($record)) {
                continue;
            }

            $error = $this->validateRow($record, $fallbackCompany);

            if ($error !== null) {
                $report['errors'][] = ['row' => $line, 'message' => $error];
                $report['skipped']++;

                continue;
            }

            try {
                $company = $this->companyForRow($record, $fallbackCompany);
                $existing = $this->existingCustomer($record, $company);
                $payload = $this->payload($record, $company);

                if ($existing && ! $updateExisting) {
                    $report['errors'][] = ['row' => $line, 'message' => 'Existing customer skipped.'];
                    $report['skipped']++;

                    continue;
                }

                if ($existing) {
                    $this->customerService->update($existing->uuid, $payload, $actor);
                    $report['updated']++;

                    continue;
                }

                $this->customerService->create($payload, $actor);
                $report['created']++;
            } catch (ApiException $exception) {
                $report['errors'][] = ['row' => $line, 'message' => $exception->getMessage()];
                $report['skipped']++;
            } catch (Throwable $exception) {
                $report['errors'][] = ['row' => $line, 'message' => 'This row could not be saved.'];
                $report['skipped']++;
            }
        }

        return $report;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'customer_number',
            'company',
            'customer_type',
            'first_name',
            'last_name',
            'email',
            'phone',
            'website',
            'industry',
            'status',
            'legal_basis',
            'processing_purpose',
            'retention_until',
            'reference',
            'legal_name',
            'registration_number',
            'primary_contact_name',
            'primary_contact_title',
            'primary_contact_email',
            'primary_contact_phone',
            'location',
            'notes',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Customer>
     */
    protected function customersForExport(array $filters): Collection
    {
        $filters = $this->customerService->listFilters($filters);
        unset($filters['per_page'], $filters['page']);

        $customers = $this->customerRepository->filteredQuery($filters)
            ->with([
                'company:id,uuid,company_name',
                'location:id,uuid,branch_name,company_id',
                'industryMaster:id,uuid,code,name',
            ])
            ->limit(self::EXPORT_LIMIT + 1)
            ->get();

        if ($customers->count() > self::EXPORT_LIMIT) {
            throw new ApiException('Narrow the filters before exporting. Exports are limited to '.self::EXPORT_LIMIT.' customers.', 422);
        }

        return $customers;
    }

    /**
     * @param  Collection<int, Customer>  $customers
     * @return list<list<string|null>>
     */
    protected function table(Collection $customers): array
    {
        $rows = [$this->headings()];

        foreach ($customers as $customer) {
            $rows[] = $this->exportRow($customer);
        }

        return $rows;
    }

    /**
     * @return list<string|null>
     */
    protected function exportRow(Customer $customer): array
    {
        $industry = $customer->industryMaster?->name ?: $customer->industry;

        return $this->ordered([
            'customer_number' => $customer->customer_number,
            'company' => $customer->company?->company_name,
            'customer_type' => $customer->customer_type?->value ?? $customer->customer_type,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'website' => $customer->website,
            'industry' => $industry,
            'status' => $customer->status?->value ?? $customer->status,
            'legal_basis' => $customer->legal_basis?->value ?? $customer->legal_basis,
            'processing_purpose' => $customer->processing_purpose,
            'retention_until' => $customer->retention_until?->format('Y-m-d'),
            'reference' => $customer->reference,
            'legal_name' => $customer->legal_name,
            'registration_number' => $customer->registration_number,
            'primary_contact_name' => $customer->primary_contact_name,
            'primary_contact_title' => $customer->primary_contact_title,
            'primary_contact_email' => $customer->primary_contact_email,
            'primary_contact_phone' => $customer->primary_contact_phone,
            'location' => $customer->location?->branch_name,
            'notes' => $customer->notes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return list<string|null>
     */
    protected function ordered(array $record): array
    {
        return array_map(
            fn (string $column): ?string => filled($record[$column] ?? null) ? (string) $record[$column] : null,
            $this->headings()
        );
    }

    /**
     * @return list<array<string, string>>
     */
    protected function exampleRecords(string $companyName): array
    {
        return [
            [
                'company' => $companyName,
                'customer_type' => 'individual',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@example.com',
                'phone' => '+447700900123',
                'industry' => 'Healthcare',
                'status' => 'active',
                'legal_basis' => 'contract',
                'processing_purpose' => 'Provide the subscribed application and support.',
                'retention_until' => '2027-12-31',
                'reference' => 'ACME-001',
                'notes' => 'Sample individual. Replace this row before importing.',
            ],
            [
                'company' => $companyName,
                'customer_type' => 'business',
                'email' => 'billing@northwind.example',
                'phone' => '+447700900456',
                'website' => 'https://northwind.example',
                'industry' => 'Healthcare',
                'status' => 'active',
                'legal_basis' => 'contract',
                'processing_purpose' => 'Subscription billing and customer support.',
                'retention_until' => '2027-12-31',
                'reference' => 'NW-100',
                'legal_name' => 'Northwind Clinic Ltd',
                'registration_number' => 'GB123456',
                'primary_contact_name' => 'Alex Morgan',
                'primary_contact_title' => 'Operations',
                'primary_contact_email' => 'alex@northwind.example',
                'primary_contact_phone' => '+447700900789',
                'notes' => 'Sample organization. Replace this row before importing.',
            ],
        ];
    }

    /**
     * @param  list<list<string|null>>  $rows
     */
    protected function streamCsv(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  list<list<string|null>>  $rows
     */
    protected function streamXlsx(array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<list<mixed>>
     */
    protected function readRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => collect($row)->filter(fn (mixed $value): bool => filled($value))->isNotEmpty()
        ));
    }

    /**
     * @param  list<mixed>  $header
     * @return list<string>
     */
    protected function normalizeHeader(array $header): array
    {
        return array_map(function (mixed $value): string {
            $column = strtolower(trim((string) $value));
            $column = str_replace([' ', '-'], '_', $column);

            return self::HEADER_ALIASES[$column] ?? $column;
        }, $header);
    }

    /**
     * @param  list<string>  $header
     */
    protected function assertRequiredColumns(array $header, ?string $defaultCompany): void
    {
        foreach (['customer_type', 'email', 'legal_basis', 'processing_purpose'] as $column) {
            if (! in_array($column, $header, true)) {
                throw new ApiException('The import file must include a '.$column.' column.', 422);
            }
        }

        if (! in_array('company', $header, true) && blank($defaultCompany)) {
            throw new ApiException('The import file must include a company column.', 422);
        }
    }

    /**
     * @param  list<string>  $header
     * @param  list<mixed>  $row
     * @return array<string, string>
     */
    protected function mapRow(array $header, array $row): array
    {
        $mapped = [];

        foreach ($header as $index => $column) {
            if ($column === '') {
                continue;
            }

            $mapped[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        return $mapped;
    }

    /**
     * @param  array<string, string>  $record
     */
    protected function rowIsEmpty(array $record): bool
    {
        return collect($record)->every(fn (mixed $value): bool => blank($value));
    }

    /**
     * @param  array<string, string>  $record
     */
    protected function validateRow(array $record, ?Company $fallbackCompany): ?string
    {
        $type = CustomerType::tryFrom((string) ($record['customer_type'] ?? ''));

        if (! $type) {
            return 'Customer type must be individual, business, or enterprise.';
        }

        if ($type->requiresPersonName() && (blank($record['first_name'] ?? null) || blank($record['last_name'] ?? null))) {
            return 'First name and last name are required for individual customers.';
        }

        if ($type->requiresCompanyName() && blank($record['legal_name'] ?? null)) {
            return 'Legal name is required for business and enterprise customers.';
        }

        if (blank($record['email'] ?? null) || ! filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email must be a valid email address.';
        }

        if (! in_array((string) ($record['legal_basis'] ?? ''), CustomerLegalBasis::values(), true)) {
            return 'Legal basis is not recognized.';
        }

        if (blank($record['processing_purpose'] ?? null)) {
            return 'Processing purpose is required.';
        }

        if (blank($record['company'] ?? null) && ! $fallbackCompany) {
            return 'Company is required.';
        }

        if (filled($record['status'] ?? null) && ! in_array($record['status'], CustomerStatus::values(), true)) {
            return 'Status is not recognized.';
        }

        if (filled($record['website'] ?? null) && ! filter_var($record['website'], FILTER_VALIDATE_URL)) {
            return 'Website must be a valid http or https URL.';
        }

        if (filled($record['phone'] ?? null) && ! $this->validPhone($record['phone'])) {
            return 'Phone must be a valid E.164 number, such as +447700900123.';
        }

        if (filled($record['primary_contact_phone'] ?? null) && ! $this->validPhone($record['primary_contact_phone'])) {
            return 'Primary contact phone must be a valid E.164 number.';
        }

        if (filled($record['primary_contact_email'] ?? null) && ! filter_var($record['primary_contact_email'], FILTER_VALIDATE_EMAIL)) {
            return 'Primary contact email must be a valid email address.';
        }

        if (filled($record['retention_until'] ?? null) && strtotime($record['retention_until']) === false) {
            return 'Retain until must be a valid date.';
        }

        if (filled($record['reference'] ?? null) && ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $record['reference'])) {
            return 'Business reference may contain letters, numbers, dots, underscores, and hyphens.';
        }

        return null;
    }

    /**
     * @param  array<string, string>  $record
     */
    protected function companyForRow(array $record, ?Company $fallbackCompany): Company
    {
        if (blank($record['company'] ?? null)) {
            if (! $fallbackCompany || ! $this->companyIsActive($fallbackCompany)) {
                throw new ApiException('Select an active company.', 422);
            }

            return $fallbackCompany;
        }

        $company = $this->resolveCompany($record['company']);

        if (! $company || ! $this->companyIsActive($company)) {
            throw new ApiException('Company "'.$record['company'].'" was not found or is not active.', 422);
        }

        return $company;
    }

    protected function resolveCompany(string $value): ?Company
    {
        return $this->companyRepository->findByNameOrIdentifier($value);
    }

    protected function companyIsActive(Company $company): bool
    {
        $status = $company->status instanceof CompanyStatus
            ? $company->status
            : CompanyStatus::tryFrom((string) $company->status);

        return $status === CompanyStatus::Active;
    }

    /**
     * @param  array<string, string>  $record
     */
    protected function existingCustomer(array $record, Company $company): ?Customer
    {
        if (filled($record['customer_number'] ?? null)) {
            $byNumber = $this->customerRepository->findActiveByNumber($record['customer_number']);

            if ($byNumber && (int) $byNumber->company_id !== (int) $company->id) {
                throw new ApiException('Customer number belongs to a different company.', 422);
            }

            if ($byNumber) {
                return $byNumber;
            }

            throw new ApiException('Customer number was not found.', 422);
        }

        return $this->customerRepository->findActiveByEmail($company->id, $record['email']);
    }

    /**
     * @param  array<string, string>  $record
     * @return array<string, mixed>
     */
    protected function payload(array $record, Company $company): array
    {
        $payload = [
            'company_id' => $company->uuid,
            'customer_type' => $record['customer_type'],
            'first_name' => $record['first_name'] ?? null,
            'last_name' => $record['last_name'] ?? null,
            'email' => $record['email'],
            'phone' => $this->nullable($record, 'phone'),
            'website' => $this->nullable($record, 'website'),
            'status' => filled($record['status'] ?? null) ? $record['status'] : CustomerStatus::Active->value,
            'legal_basis' => $record['legal_basis'],
            'processing_purpose' => $record['processing_purpose'],
            'retention_until' => $this->nullable($record, 'retention_until'),
            'reference' => $this->nullable($record, 'reference'),
            'legal_name' => $this->nullable($record, 'legal_name'),
            'registration_number' => $this->nullable($record, 'registration_number'),
            'primary_contact_name' => $this->nullable($record, 'primary_contact_name'),
            'primary_contact_title' => $this->nullable($record, 'primary_contact_title'),
            'primary_contact_email' => $this->nullable($record, 'primary_contact_email'),
            'primary_contact_phone' => $this->nullable($record, 'primary_contact_phone'),
            'notes' => $this->nullable($record, 'notes'),
        ];

        if (filled($record['industry'] ?? null)) {
            $industry = $this->industryRepository->findByNameOrCode($record['industry']);

            if ($industry) {
                $payload['industry_id'] = $industry->uuid;
            } else {
                $payload['industry'] = $record['industry'];
            }
        }

        if (filled($record['location'] ?? null)) {
            $location = $this->locationRepository->findByIdentifier($record['location'])
                ?? $this->locationRepository->findByBranchName($company->id, $record['location']);

            if (! $location || (int) $location->company_id !== (int) $company->id) {
                throw new ApiException('Location "'.$record['location'].'" was not found for this company.', 422);
            }

            $payload['location_id'] = $location->uuid;
        }

        return $payload;
    }

    /**
     * @param  array<string, string>  $record
     */
    protected function nullable(array $record, string $key): ?string
    {
        return filled($record[$key] ?? null) ? $record[$key] : null;
    }

    protected function validPhone(string $value): bool
    {
        $phone = PhoneNumber::canonicalize($value);

        if ($phone === null) {
            return false;
        }

        if (! str_starts_with($phone, '+') && preg_match('/^\d{8,15}$/', $phone) === 1) {
            $phone = '+'.$phone;
        }

        return PhoneNumber::isE164($phone);
    }
}
