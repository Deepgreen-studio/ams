<?php

namespace App\Domains\Companies\Services;

use App\Domains\Companies\Events\DepartmentCreated;
use App\Domains\Companies\Events\DepartmentUpdated;
use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Companies\Repositories\DepartmentRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepository $departmentRepository,
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

        return $this->departmentRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Department
    {
        return DB::transaction(function () use ($data, $actor): Department {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);

            /** @var Department $department */
            $department = $this->departmentRepository->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new DepartmentCreated($department->load('company'), $actor));

            return $department->load('company');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Department
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Department {
            $department = $this->departmentRepository->findByIdentifierOrFail($identifier);
            $payload = array_intersect_key($data, array_flip(['name', 'description', 'status']));
            $payload['updated_by'] = $actor->id;

            $department->fill($payload);
            $department->save();

            event(new DepartmentUpdated($department->refresh()->load('company'), $actor));

            return $department->load('company');
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $department = $this->departmentRepository->findByIdentifierOrFail($identifier);
            $department->updated_by = $actor->id;
            $department->save();
            $department->delete();
        });
    }
}
