<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Enums\CustomerDocumentCategory;
use App\Domains\Customers\Enums\CustomerDocumentStatus;
use App\Domains\Customers\Events\CustomerDocumentDeleted;
use App\Domains\Customers\Events\CustomerDocumentRestored;
use App\Domains\Customers\Events\CustomerDocumentUpdated;
use App\Domains\Customers\Events\CustomerDocumentUploaded;
use App\Domains\Customers\Events\CustomerDocumentVersionUploaded;
use App\Domains\Customers\Models\CustomerDocument;
use App\Domains\Customers\Repositories\CustomerDocumentRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDocumentService
{
    /**
     * @var list<string>
     */
    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg',
        'zip', 'rar', '7z',
    ];

    private const MAX_UPLOAD_KB = 51200;

    public function __construct(
        private readonly CustomerDocumentRepository $documentRepository,
        private readonly CustomerRepository $customerRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{documents: LengthAwarePaginator, statistics: array<string, int>, folders: list<array{category: string, label: string, count: int}>}
     */
    public function library(array $filters = []): array
    {
        $filters = $this->resolveFilters($filters);
        $customerId = isset($filters['customer_id']) ? (int) $filters['customer_id'] : null;

        return [
            'documents' => $this->documentRepository->paginateFiltered($filters),
            'statistics' => $this->documentRepository->statistics($customerId),
            'folders' => $this->documentRepository->folders($customerId),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->documentRepository->paginateFiltered($this->resolveFilters($filters));
    }

    /**
     * @return list<array{category: string, label: string, count: int}>
     */
    public function folders(?string $customerIdentifier = null): array
    {
        $customerId = null;
        if (! blank($customerIdentifier)) {
            $customerId = $this->customerRepository->findByIdentifierOrFail($customerIdentifier)->id;
        }

        return $this->documentRepository->folders($customerId);
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

        return $this->documentRepository->statistics($customerId);
    }

    public function find(string $identifier, bool $withTrashed = false): CustomerDocument
    {
        return $this->documentRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): CustomerDocument
    {
        return $this->find($identifier)->load([
            'customer:id,uuid,first_name,last_name,company_name,email,customer_type,status',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upload(array $data, UploadedFile $file, User $actor): CustomerDocument
    {
        return DB::transaction(function () use ($data, $file, $actor): CustomerDocument {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $data['customer_id']);
            $this->assertValidUpload($file);

            $disk = (string) config('filesystems.customer_documents_disk', 'public');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $storedName = sprintf('%s.%s', Str::uuid()->toString(), $extension);
            $directory = sprintf('customer-documents/%s/%s', $customer->uuid, $data['category'] ?? 'custom');
            $path = $file->storeAs($directory, $storedName, $disk);

            if (! $path) {
                throw new ApiException('Unable to store customer document.', 500);
            }

            $status = $data['status'] ?? CustomerDocumentStatus::Active->value;
            $expiresAt = ! empty($data['expires_at']) ? Carbon::parse((string) $data['expires_at']) : null;

            if ($expiresAt && $expiresAt->isPast()) {
                $status = CustomerDocumentStatus::Expired->value;
            }

            $document = $this->documentRepository->createDocument([
                'customer_id' => $customer->id,
                'document_group_uuid' => (string) Str::uuid(),
                'version' => 1,
                'is_current' => true,
                'name' => $data['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'category' => $data['category'] ?? CustomerDocumentCategory::Custom->value,
                'status' => $status,
                'disk' => $disk,
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'extension' => $extension,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
                'expires_at' => $expiresAt,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new CustomerDocumentUploaded($document, $actor));

            return $document->load([
                'customer:id,uuid,first_name,last_name,company_name,email',
                'creator:id,uuid,full_name,email',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function uploadVersion(string $identifier, UploadedFile $file, array $data, User $actor): CustomerDocument
    {
        return DB::transaction(function () use ($identifier, $file, $data, $actor): CustomerDocument {
            $current = $this->documentRepository->findByIdentifierOrFail($identifier);
            $this->assertValidUpload($file);

            $disk = (string) config('filesystems.customer_documents_disk', 'public');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $storedName = sprintf('%s.%s', Str::uuid()->toString(), $extension);
            $category = $data['category'] ?? $current->category?->value ?? $current->category;

            if (! $current->relationLoaded('customer')) {
                $current->load('customer:id,uuid');
            }

            $directory = sprintf('customer-documents/%s/%s', $current->customer->uuid, $category);
            $path = $file->storeAs($directory, $storedName, $disk);

            if (! $path) {
                throw new ApiException('Unable to store document version.', 500);
            }

            $this->documentRepository->updateDocument($current, [
                'is_current' => false,
                'updated_by' => $actor->id,
            ]);

            $nextVersion = $this->documentRepository->latestVersionNumber($current->document_group_uuid) + 1;
            $expiresAt = array_key_exists('expires_at', $data)
                ? (! blank($data['expires_at']) ? Carbon::parse((string) $data['expires_at']) : null)
                : $current->expires_at;

            $status = $data['status'] ?? $current->status?->value ?? CustomerDocumentStatus::Active->value;
            if ($expiresAt && $expiresAt->isPast()) {
                $status = CustomerDocumentStatus::Expired->value;
            }

            $document = $this->documentRepository->createDocument([
                'customer_id' => $current->customer_id,
                'document_group_uuid' => $current->document_group_uuid,
                'version' => $nextVersion,
                'is_current' => true,
                'name' => $data['name'] ?? $current->name,
                'category' => $category,
                'status' => $status,
                'disk' => $disk,
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'extension' => $extension,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
                'expires_at' => $expiresAt,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $current->notes,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new CustomerDocumentVersionUploaded($document, $actor));

            return $document->load([
                'customer:id,uuid,first_name,last_name,company_name,email',
                'creator:id,uuid,full_name,email',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): CustomerDocument
    {
        return DB::transaction(function () use ($identifier, $data, $actor): CustomerDocument {
            $document = $this->documentRepository->findByIdentifierOrFail($identifier);
            $payload = array_intersect_key($data, array_flip(['name', 'category', 'status', 'expires_at', 'notes']));

            if (array_key_exists('expires_at', $payload)) {
                $payload['expires_at'] = blank($payload['expires_at'])
                    ? null
                    : Carbon::parse((string) $payload['expires_at']);
            }

            if (! empty($payload['expires_at']) && $payload['expires_at']->isPast()) {
                $payload['status'] = CustomerDocumentStatus::Expired->value;
            }

            if (array_key_exists('notes', $payload) && blank($payload['notes'])) {
                $payload['notes'] = null;
            }

            $payload['updated_by'] = $actor->id;
            $updated = $this->documentRepository->updateDocument($document, $payload);
            event(new CustomerDocumentUpdated($updated, $actor));

            return $updated->load([
                'customer:id,uuid,first_name,last_name,company_name,email',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ]);
        });
    }

    public function versions(string $identifier): Collection
    {
        $document = $this->documentRepository->findByIdentifierOrFail($identifier);

        return $this->documentRepository->versionsForGroup(
            $document->document_group_uuid,
            (int) $document->customer_id
        );
    }

    public function download(string $identifier): StreamedResponse
    {
        $document = $this->documentRepository->findByIdentifierOrFail($identifier);
        $this->assertFileExists($document);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_filename
        );
    }

    public function preview(string $identifier): StreamedResponse
    {
        $document = $this->documentRepository->findByIdentifierOrFail($identifier);
        $this->assertFileExists($document);

        if (! $document->isPreviewable()) {
            throw new ApiException('Preview is not available for this file type. Use download instead.', 422);
        }

        return Storage::disk($document->disk)->response(
            $document->path,
            $document->original_filename,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$document->original_filename.'"',
            ]
        );
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $document = $this->documentRepository->findByIdentifierOrFail($identifier);
            $this->documentRepository->updateDocument($document, [
                'status' => CustomerDocumentStatus::Archived->value,
                'updated_by' => $actor->id,
            ]);
            $document->delete();
            event(new CustomerDocumentDeleted($document, $actor));
        });
    }

    public function restore(string $identifier, User $actor): CustomerDocument
    {
        return DB::transaction(function () use ($identifier, $actor): CustomerDocument {
            $document = $this->documentRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $document->trashed()) {
                throw new ApiException('Document is not archived.', 422);
            }

            $document->restore();
            $restored = $this->documentRepository->updateDocument($document, [
                'status' => CustomerDocumentStatus::Active->value,
                'updated_by' => $actor->id,
            ]);
            event(new CustomerDocumentRestored($restored, $actor));

            return $restored;
        });
    }

    public function timeline(string $identifier, int $limit = 50): Collection
    {
        return $this->documentRepository->timeline($this->find($identifier), $limit);
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

    protected function assertValidUpload(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        if ($extension === '' || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new ApiException("File type .{$extension} is not allowed.", 422);
        }

        $sizeKb = (int) ceil(($file->getSize() ?: 0) / 1024);
        if ($sizeKb > self::MAX_UPLOAD_KB) {
            throw new ApiException('File exceeds maximum upload size of 50 MB.', 422);
        }
    }

    protected function assertFileExists(CustomerDocument $document): void
    {
        if (blank($document->path) || ! Storage::disk($document->disk)->exists($document->path)) {
            throw new ApiException('Document file is missing from storage.', 404);
        }
    }
}
