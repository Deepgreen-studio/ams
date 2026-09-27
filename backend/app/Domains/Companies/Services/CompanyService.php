<?php

namespace App\Domains\Companies\Services;

use App\Domains\Companies\Events\BrandingUpdated;
use App\Domains\Companies\Events\CompanyCreated;
use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Audit\Models\SystemEvent;
use App\Domains\Audit\Repositories\ActivityRepository;
use App\Domains\Audit\Repositories\EventRepository;
use App\Domains\Companies\Events\CompanyDeleted;
use App\Domains\Companies\Events\CompanyRestored;
use App\Domains\Companies\Events\CompanyStatusChanged;
use App\Domains\Companies\Events\CompanyUpdated;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Support\PhoneNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyService
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly EventRepository $eventRepository,
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->companyRepository->paginateFiltered($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): Company
    {
        return $this->companyRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): Company
    {
        $company = $this->find($identifier);
        $company->load([
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'departments',
            'teams.manager:id,uuid,full_name,email',
            'locations',
        ])->loadCount(['departments', 'teams', 'locations']);

        return $company;
    }

    /**
     * @return array{activities: Collection<int, ActivityLog>, important_events: Collection<int, SystemEvent>}
     */
    public function activityHistory(Company $company, int $limit = 30): array
    {
        return [
            'activities' => $this->activityRepository->forSubject($company->getMorphClass(), $company->getKey(), $limit),
            'important_events' => $this->eventRepository->forCompany((string) $company->uuid, $limit),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $actor): Company
    {
        return DB::transaction(function () use ($data, $actor): Company {
            $payload = $this->preparePayload($data);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['status'] = $payload['status'] ?? 'active';

            $company = $this->companyRepository->createCompany($payload);
            event(new CompanyCreated($company, $actor));

            return $company;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $identifier, array $data, User $actor): Company
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Company {
            $company = $this->companyRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $company->status?->value ?? (string) $company->status;
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            $changedKeys = array_values(array_diff(array_keys($payload), ['updated_by']));
            $statusOnly = $changedKeys === ['status'];
            $updated = $statusOnly
                ? activity()->withoutLogs(fn () => $this->companyRepository->updateCompany($company, $payload))
                : $this->companyRepository->updateCompany($company, $payload);
            $nextStatus = $updated->status?->value ?? (string) $updated->status;
            $statusChanged = $previousStatus !== $nextStatus;

            if ($statusChanged) {
                event(new CompanyStatusChanged($updated, $actor, $previousStatus, $nextStatus));
            }

            if (! $statusChanged || $changedKeys !== ['status']) {
                event(new CompanyUpdated($updated, $actor));
            }

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $company = $this->companyRepository->findByIdentifierOrFail($identifier);
            $this->companyRepository->updateCompany($company, ['updated_by' => $actor->id]);
            $company->delete();

            $stamp = $company->deleted_at;
            foreach (['departments', 'teams', 'locations'] as $relation) {
                $company->{$relation}()->update([
                    'deleted_at' => $stamp,
                    'updated_at' => $stamp,
                    'updated_by' => $actor->id,
                ]);
            }

            event(new CompanyDeleted($company, $actor));
        });
    }

    public function restore(string $identifier, User $actor): Company
    {
        return DB::transaction(function () use ($identifier, $actor): Company {
            $company = $this->companyRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $company->trashed()) {
                throw new ApiException('Company is not deleted.', 422);
            }

            $stamp = $company->deleted_at;
            $company->restore();

            foreach (['departments', 'teams', 'locations'] as $relation) {
                $company->{$relation}()->onlyTrashed()->where('deleted_at', $stamp)->restore();
            }

            $restored = $this->companyRepository->updateCompany($company, ['updated_by' => $actor->id]);
            event(new CompanyRestored($restored, $actor));

            return $restored;
        });
    }

    public function uploadLogo(string $identifier, UploadedFile $file, User $actor): Company
    {
        return $this->uploadMedia($identifier, $file, 'logo', 'logos', $actor);
    }

    public function uploadFavicon(string $identifier, UploadedFile $file, User $actor): Company
    {
        return $this->uploadMedia($identifier, $file, 'favicon', 'favicons', $actor);
    }

    /**
     * @param array<string, mixed> $branding
     */
    public function updateBranding(string $identifier, array $branding, User $actor): Company
    {
        return DB::transaction(function () use ($identifier, $branding, $actor): Company {
            $company = $this->companyRepository->findByIdentifierOrFail($identifier);
            $payload = array_intersect_key($branding, array_flip([
                'primary_color',
                'secondary_color',
                'timezone',
                'language',
                'currency',
                'date_format',
                'time_format',
                'business_hours',
                'settings',
            ]));
            $payload['updated_by'] = $actor->id;

            $updated = $this->companyRepository->updateCompany($company, $payload);
            event(new BrandingUpdated($updated, $actor));

            return $updated;
        });
    }

    protected function uploadMedia(
        string $identifier,
        UploadedFile $file,
        string $column,
        string $directory,
        User $actor
    ): Company {
        return DB::transaction(function () use ($identifier, $file, $column, $directory, $actor): Company {
            $company = $this->companyRepository->findByIdentifierOrFail($identifier);
            $disk = config('filesystems.company_media_disk', 'public');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
            $filename = sprintf('%s.%s', Str::uuid()->toString(), $extension);
            $path = $file->storeAs("companies/{$directory}", $filename, $disk);

            if (! $path) {
                throw new ApiException('Unable to store company media.', 500);
            }

            $previous = $company->{$column};
            $updated = $this->companyRepository->updateCompany($company, [
                $column => $path,
                'updated_by' => $actor->id,
            ]);

            $this->deleteMediaFile($previous);
            event(new BrandingUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'company_name',
            'legal_name',
            'registration_number',
            'tax_number',
            'email',
            'phone',
            'website',
            'address',
            'city',
            'state',
            'postal_code',
            'country',
            'timezone',
            'language',
            'currency',
            'date_format',
            'time_format',
            'business_hours',
            'settings',
            'primary_color',
            'secondary_color',
            'status',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['registration_number', 'tax_number', 'email', 'phone', 'website'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if (array_key_exists('phone', $payload) && $payload['phone'] !== null) {
            $payload['phone'] = PhoneNumber::store($payload['phone']);
        }

        if (! $isUpdate && empty($payload['timezone'])) {
            $payload['timezone'] = 'UTC';
        }

        return $payload;
    }

    protected function deleteMediaFile(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $disk = config('filesystems.company_media_disk', 'public');
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
