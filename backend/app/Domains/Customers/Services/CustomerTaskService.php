<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Enums\CustomerTaskPriority;
use App\Domains\Customers\Enums\CustomerTaskStatus;
use App\Domains\Customers\Events\CustomerTaskCompleted;
use App\Domains\Customers\Events\CustomerTaskCreated;
use App\Domains\Customers\Events\CustomerTaskDeleted;
use App\Domains\Customers\Events\CustomerTaskRestored;
use App\Domains\Customers\Events\CustomerTaskUpdated;
use App\Domains\Customers\Models\CustomerTask;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Domains\Customers\Repositories\CustomerTaskRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerTaskService
{
    public function __construct(
        private readonly CustomerTaskRepository $taskRepository,
        private readonly CustomerRepository $customerRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{tasks: LengthAwarePaginator, statistics: array<string, int>}
     */
    public function list(array $filters = []): array
    {
        $filters = $this->resolveFilters($filters);
        $customerId = isset($filters['customer_id']) ? (int) $filters['customer_id'] : null;

        return [
            'tasks' => $this->taskRepository->paginateFiltered($filters),
            'statistics' => $this->taskRepository->statistics($customerId),
        ];
    }

    public function find(string $identifier, bool $withTrashed = false): CustomerTask
    {
        return $this->taskRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): CustomerTask
    {
        return $this->find($identifier)->load([
            'customer:id,uuid,first_name,last_name,company_name,email',
            'assignee:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CustomerTask
    {
        return DB::transaction(function () use ($data, $actor): CustomerTask {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $data['customer_id']);
            $task = $this->taskRepository->createTask([
                'customer_id' => $customer->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? CustomerTaskStatus::Open->value,
                'priority' => $data['priority'] ?? CustomerTaskPriority::Medium->value,
                'due_at' => ! empty($data['due_at']) ? Carbon::parse((string) $data['due_at']) : null,
                'remind_at' => ! empty($data['remind_at']) ? Carbon::parse((string) $data['remind_at']) : null,
                'assigned_to' => $this->resolveAssigneeId($data['assigned_to'] ?? null),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new CustomerTaskCreated($task, $actor));

            return $task->load(['assignee:id,uuid,full_name,email', 'customer:id,uuid,first_name,last_name,company_name,email']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): CustomerTask
    {
        return DB::transaction(function () use ($identifier, $data, $actor): CustomerTask {
            $task = $this->taskRepository->findByIdentifierOrFail($identifier);
            $payload = array_intersect_key($data, array_flip([
                'title', 'description', 'status', 'priority', 'due_at', 'remind_at', 'assigned_to',
            ]));

            foreach (['due_at', 'remind_at'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $payload[$field] = blank($payload[$field]) ? null : Carbon::parse((string) $payload[$field]);
                }
            }
            if (array_key_exists('assigned_to', $payload)) {
                $payload['assigned_to'] = $this->resolveAssigneeId($payload['assigned_to']);
            }
            if (array_key_exists('description', $payload) && blank($payload['description'])) {
                $payload['description'] = null;
            }

            if (($payload['status'] ?? null) === CustomerTaskStatus::Completed->value && ! $task->completed_at) {
                $payload['completed_at'] = now();
            }
            if (array_key_exists('status', $payload) && $payload['status'] !== CustomerTaskStatus::Completed->value) {
                $payload['completed_at'] = null;
            }

            $payload['updated_by'] = $actor->id;
            $updated = $this->taskRepository->updateTask($task, $payload);

            if (($payload['status'] ?? null) === CustomerTaskStatus::Completed->value) {
                event(new CustomerTaskCompleted($updated, $actor));
            } else {
                event(new CustomerTaskUpdated($updated, $actor));
            }

            return $updated->load(['assignee:id,uuid,full_name,email', 'customer:id,uuid,first_name,last_name,company_name,email']);
        });
    }

    public function complete(string $identifier, User $actor): CustomerTask
    {
        return $this->update($identifier, ['status' => CustomerTaskStatus::Completed->value], $actor);
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $task = $this->taskRepository->findByIdentifierOrFail($identifier);
            $this->taskRepository->updateTask($task, ['updated_by' => $actor->id]);
            $task->delete();
            event(new CustomerTaskDeleted($task, $actor));
        });
    }

    public function restore(string $identifier, User $actor): CustomerTask
    {
        return DB::transaction(function () use ($identifier, $actor): CustomerTask {
            $task = $this->taskRepository->findByIdentifierOrFail($identifier, withTrashed: true);
            if (! $task->trashed()) {
                throw new ApiException('Task is not archived.', 422);
            }
            $task->restore();
            $restored = $this->taskRepository->updateTask($task, [
                'status' => CustomerTaskStatus::Open->value,
                'updated_by' => $actor->id,
            ]);
            event(new CustomerTaskRestored($restored, $actor));

            return $restored;
        });
    }

    /**
     * @return Collection<int, CustomerTask>
     */
    public function calendar(?string $customerIdentifier = null, ?string $from = null, ?string $to = null): Collection
    {
        $customerId = null;
        if (! blank($customerIdentifier)) {
            $customerId = $this->customerRepository->findByIdentifierOrFail($customerIdentifier)->id;
        }

        return $this->taskRepository->reminders(
            $customerId,
            $from ? Carbon::parse($from)->startOfDay() : null,
            $to ? Carbon::parse($to)->endOfDay() : null
        );
    }

    public function timeline(string $identifier, int $limit = 50): Collection
    {
        return $this->taskRepository->timeline($this->find($identifier), $limit);
    }

    protected function resolveAssigneeId(mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        $user = User::query()->where('uuid', (string) $identifier)->first();
        if (! $user) {
            throw new ApiException('Assigned user not found.', 422);
        }

        return $user->id;
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

        $assignee = $filters['assigned_to'] ?? null;
        if (! empty($assignee) && ! is_numeric($assignee)) {
            $filters['assigned_to'] = $this->resolveAssigneeId($assignee);
        }

        return $filters;
    }
}
