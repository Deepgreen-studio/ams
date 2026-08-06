<?php

namespace App\Domains\Companies\Services;

use App\Domains\Companies\Events\LocationCreated;
use App\Domains\Companies\Models\CompanyLocation;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Companies\Repositories\LocationRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LocationService
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly CompanyRepository $companyRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier);
            $filters['company_id'] = $company->id;
        }

        return $this->locationRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CompanyLocation
    {
        return DB::transaction(function () use ($data, $actor): CompanyLocation {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);

            /** @var CompanyLocation $location */
            $location = $this->locationRepository->create([
                'company_id' => $company->id,
                'branch_name' => $data['branch_name'],
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'is_headquarters' => (bool) ($data['is_headquarters'] ?? false),
                'status' => $data['status'] ?? 'active',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new LocationCreated($location->load('company'), $actor));

            return $location->load('company');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): CompanyLocation
    {
        return DB::transaction(function () use ($identifier, $data, $actor): CompanyLocation {
            $location = $this->locationRepository->findByIdentifierOrFail($identifier);
            $payload = array_intersect_key($data, array_flip([
                'branch_name',
                'address',
                'city',
                'state',
                'country',
                'postal_code',
                'phone',
                'email',
                'is_headquarters',
                'status',
            ]));
            $payload['updated_by'] = $actor->id;

            $location->fill($payload);
            $location->save();

            return $location->refresh()->load('company');
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $location = $this->locationRepository->findByIdentifierOrFail($identifier);
            $location->updated_by = $actor->id;
            $location->save();
            $location->delete();
        });
    }
}
