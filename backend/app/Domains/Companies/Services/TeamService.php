<?php

namespace App\Domains\Companies\Services;

use App\Domains\Companies\Events\TeamCreated;
use App\Domains\Companies\Models\Team;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Companies\Repositories\DepartmentRepository;
use App\Domains\Companies\Repositories\TeamRepository;
use App\Domains\Users\Repositories\UserRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly UserRepository $userRepository
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

        $departmentIdentifier = $filters['department'] ?? $filters['department_id'] ?? null;
        if (! empty($departmentIdentifier) && ! is_numeric($departmentIdentifier)) {
            $department = $this->departmentRepository->findByIdentifierOrFail((string) $departmentIdentifier);
            $filters['department_id'] = $department->id;
        }

        return $this->teamRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Team
    {
        return DB::transaction(function () use ($data, $actor): Team {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $departmentId = null;
            $managerId = null;

            if (! empty($data['department_id'])) {
                $department = $this->departmentRepository->findByIdentifierOrFail((string) $data['department_id']);
                if ($department->company_id !== $company->id) {
                    throw new ApiException('Department does not belong to the selected company.', 422);
                }
                $departmentId = $department->id;
            }

            if (! empty($data['manager_id'])) {
                $manager = $this->userRepository->findByIdentifierOrFail((string) $data['manager_id']);
                $managerId = $manager->id;
            }

            /** @var Team $team */
            $team = $this->teamRepository->create([
                'company_id' => $company->id,
                'department_id' => $departmentId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'manager_id' => $managerId,
                'status' => $data['status'] ?? 'active',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new TeamCreated($team->load(['company', 'department', 'manager']), $actor));

            return $team->load(['company', 'department', 'manager']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Team
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Team {
            $team = $this->teamRepository->findByIdentifierOrFail($identifier);
            $payload = array_intersect_key($data, array_flip(['name', 'description', 'status']));

            if (array_key_exists('department_id', $data)) {
                if (blank($data['department_id'])) {
                    $payload['department_id'] = null;
                } else {
                    $department = $this->departmentRepository->findByIdentifierOrFail((string) $data['department_id']);
                    if ($department->company_id !== $team->company_id) {
                        throw new ApiException('Department does not belong to the team company.', 422);
                    }
                    $payload['department_id'] = $department->id;
                }
            }

            if (array_key_exists('manager_id', $data)) {
                if (blank($data['manager_id'])) {
                    $payload['manager_id'] = null;
                } else {
                    $manager = $this->userRepository->findByIdentifierOrFail((string) $data['manager_id']);
                    $payload['manager_id'] = $manager->id;
                }
            }

            $payload['updated_by'] = $actor->id;
            $team->fill($payload);
            $team->save();

            return $team->refresh()->load(['company', 'department', 'manager']);
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $team = $this->teamRepository->findByIdentifierOrFail($identifier);
            $team->updated_by = $actor->id;
            $team->save();
            $team->delete();
        });
    }
}
