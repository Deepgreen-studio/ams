<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Enums\CustomerContactStatus;
use App\Domains\Customers\Enums\CustomerContactType;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerContact;
use App\Domains\Customers\Repositories\CustomerContactRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Support\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerContactImportService
{
    private const MAX_ROWS = 1000;

    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerContactRepository $customerContactRepository,
        private readonly CustomerContactService $customerContactService,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function import(UploadedFile $file, string $customerIdentifier, bool $updateExisting, User $actor): array
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);
        $rows = $this->readRows($file);

        if ($rows === []) {
            throw new ApiException('The import file is empty.', 422);
        }

        $header = array_map(
            fn (mixed $value): string => strtolower(trim((string) $value)),
            array_shift($rows) ?? []
        );

        if (! in_array('name', $header, true)) {
            throw new ApiException('The import file must include a name column.', 422);
        }

        if (count($rows) > self::MAX_ROWS) {
            throw new ApiException('Import files are limited to ' . self::MAX_ROWS . ' contacts.', 422);
        }

        $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $record = $this->mapRow($header, $row);

            if ($this->rowIsEmpty($record)) {
                continue;
            }

            $error = $this->validateRow($record);

            if ($error !== null) {
                $report['errors'][] = ['row' => $line, 'message' => $error];
                $report['skipped']++;

                continue;
            }

            $duplicate = $this->customerContactRepository->findActiveDuplicate(
                $customer->id,
                $record['email'],
                $record['name']
            );

            if ($duplicate && ! $updateExisting) {
                $report['errors'][] = ['row' => $line, 'message' => 'Duplicate contact skipped.'];
                $report['skipped']++;

                continue;
            }

            $payload = $this->payload($record, $customer);

            if ($duplicate && $updateExisting) {
                $this->customerContactService->update($duplicate->uuid, $payload, $actor);
                $report['updated']++;

                continue;
            }

            $this->customerContactService->create($payload, $actor);
            $report['created']++;
        }

        return $report;
    }

    public function export(string $customerIdentifier, string $format = 'csv'): StreamedResponse
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);
        $contacts = $customer->contacts()->orderBy('name')->get();
        $filename = 'customer-contacts-' . $customer->customer_number . '-' . now()->format('Ymd-His');

        if ($format === 'xlsx') {
            return $this->exportXlsx($contacts, $filename . '.xlsx');
        }

        return response()->streamDownload(function () use ($contacts): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->headings());

            foreach ($contacts as $contact) {
                fputcsv($handle, $this->exportRow($contact));
            }

            fclose($handle);
        }, $filename . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @param Collection<int, CustomerContact> $contacts
     */
    protected function exportXlsx($contacts, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [$this->headings()];

        foreach ($contacts as $contact) {
            $rows[] = $this->exportRow($contact);
        }

        $sheet->fromArray($rows);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<string>
     */
    protected function headings(): array
    {
        return ['name', 'email', 'phone', 'contact_type', 'responsibilities', 'position', 'department', 'status', 'notes'];
    }

    /**
     * @return list<string|null>
     */
    protected function exportRow(CustomerContact $contact): array
    {
        $responsibilities = $contact->responsibilities ?: [$contact->contact_type?->value ?? $contact->contact_type];

        return [
            $contact->name,
            $contact->email,
            $contact->phone,
            $contact->contact_type?->value ?? $contact->contact_type,
            implode('|', array_filter($responsibilities)),
            $contact->position,
            $contact->department,
            $contact->status?->value ?? $contact->status,
            $contact->notes,
        ];
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
     * @param list<string> $header
     * @param list<mixed> $row
     * @return array<string, mixed>
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
     * @param array<string, mixed> $record
     */
    protected function rowIsEmpty(array $record): bool
    {
        return collect($record)->every(fn (mixed $value): bool => blank($value));
    }

    /**
     * @param array<string, mixed> $record
     */
    protected function validateRow(array $record): ?string
    {
        if (blank($record['name'] ?? null)) {
            return 'Name is required.';
        }

        $type = (string) ($record['contact_type'] ?? CustomerContactType::Support->value);

        if (! in_array($type, CustomerContactType::values(), true)) {
            return 'Contact type is not recognized.';
        }

        if (filled($record['email'] ?? null) && ! filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email must be a valid email address.';
        }

        if (filled($record['phone'] ?? null)) {
            $phone = $this->normalizePhone($record['phone']);

            if (! PhoneNumber::isE164($phone)) {
                return 'Phone must be a valid E.164 number.';
            }
        }

        $status = (string) ($record['status'] ?? CustomerContactStatus::Active->value);

        if (! in_array($status, CustomerContactStatus::values(), true)) {
            return 'Status is not recognized.';
        }

        foreach ($this->responsibilityValues($record) as $responsibility) {
            if (! in_array($responsibility, CustomerContactType::values(), true)) {
                return 'Responsibility "' . $responsibility . '" is not recognized.';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    protected function payload(array $record, Customer $customer): array
    {
        return [
            'customer_id' => $customer->uuid,
            'name' => $record['name'] ?? '',
            'email' => filled($record['email'] ?? null) ? $record['email'] : null,
            'phone' => filled($record['phone'] ?? null) ? $this->normalizePhone($record['phone']) : null,
            'contact_type' => filled($record['contact_type'] ?? null)
                ? $record['contact_type']
                : CustomerContactType::Support->value,
            'responsibilities' => $this->responsibilityValues($record),
            'position' => filled($record['position'] ?? null) ? $record['position'] : null,
            'department' => filled($record['department'] ?? null) ? $record['department'] : null,
            'status' => filled($record['status'] ?? null)
                ? $record['status']
                : CustomerContactStatus::Active->value,
            'notes' => filled($record['notes'] ?? null) ? $record['notes'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $record
     * @return list<string>
     */
    protected function responsibilityValues(array $record): array
    {
        $raw = (string) ($record['responsibilities'] ?? '');

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $value): string => trim($value),
            preg_split('/[|,]/', $raw) ?: []
        )));
    }

    protected function normalizePhone(mixed $value): ?string
    {
        $canonical = PhoneNumber::canonicalize($value);

        if ($canonical === null || $canonical === '') {
            return null;
        }

        if (! str_starts_with($canonical, '+') && preg_match('/^\d{8,15}$/', $canonical) === 1) {
            $canonical = '+' . $canonical;
        }

        return $canonical;
    }
}
