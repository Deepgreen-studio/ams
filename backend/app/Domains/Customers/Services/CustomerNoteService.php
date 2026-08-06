<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Enums\CustomerNoteStatus;
use App\Domains\Customers\Enums\CustomerNoteType;
use App\Domains\Customers\Events\CustomerNoteCreated;
use App\Domains\Customers\Events\CustomerNoteDeleted;
use App\Domains\Customers\Events\CustomerNoteRestored;
use App\Domains\Customers\Events\CustomerNoteUpdated;
use App\Domains\Customers\Models\CustomerNote;
use App\Domains\Customers\Repositories\CustomerNoteRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerNoteService
{
    public function __construct(
        private readonly CustomerNoteRepository $noteRepository,
        private readonly CustomerRepository $customerRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{notes: LengthAwarePaginator, statistics: array<string, int>}
     */
    public function list(array $filters = []): array
    {
        $filters = $this->resolveFilters($filters);
        $customerId = isset($filters['customer_id']) ? (int) $filters['customer_id'] : null;

        return [
            'notes' => $this->noteRepository->paginateFiltered($filters),
            'statistics' => $this->noteRepository->statistics($customerId),
        ];
    }

    public function find(string $identifier, bool $withTrashed = false): CustomerNote
    {
        return $this->noteRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): CustomerNote
    {
        return $this->find($identifier)->load([
            'customer:id,uuid,first_name,last_name,company_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CustomerNote
    {
        return DB::transaction(function () use ($data, $actor): CustomerNote {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $data['customer_id']);
            $note = $this->noteRepository->createNote([
                'customer_id' => $customer->id,
                'note_type' => $data['note_type'] ?? CustomerNoteType::General->value,
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'is_pinned' => (bool) ($data['is_pinned'] ?? false),
                'status' => $data['status'] ?? CustomerNoteStatus::Active->value,
                'occurred_at' => ! empty($data['occurred_at']) ? Carbon::parse((string) $data['occurred_at']) : now(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new CustomerNoteCreated($note, $actor));

            return $note->load(['customer:id,uuid,first_name,last_name,company_name,email', 'creator:id,uuid,full_name,email']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): CustomerNote
    {
        return DB::transaction(function () use ($identifier, $data, $actor): CustomerNote {
            $note = $this->noteRepository->findByIdentifierOrFail($identifier);
            $payload = array_intersect_key($data, array_flip(['note_type', 'title', 'body', 'is_pinned', 'status', 'occurred_at']));

            if (array_key_exists('occurred_at', $payload)) {
                $payload['occurred_at'] = blank($payload['occurred_at']) ? null : Carbon::parse((string) $payload['occurred_at']);
            }
            if (array_key_exists('is_pinned', $payload)) {
                $payload['is_pinned'] = (bool) $payload['is_pinned'];
            }
            if (array_key_exists('title', $payload) && blank($payload['title'])) {
                $payload['title'] = null;
            }

            $payload['updated_by'] = $actor->id;
            $updated = $this->noteRepository->updateNote($note, $payload);
            event(new CustomerNoteUpdated($updated, $actor));

            return $updated->load(['customer:id,uuid,first_name,last_name,company_name,email', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $note = $this->noteRepository->findByIdentifierOrFail($identifier);
            $this->noteRepository->updateNote($note, [
                'status' => CustomerNoteStatus::Archived->value,
                'updated_by' => $actor->id,
            ]);
            $note->delete();
            event(new CustomerNoteDeleted($note, $actor));
        });
    }

    public function restore(string $identifier, User $actor): CustomerNote
    {
        return DB::transaction(function () use ($identifier, $actor): CustomerNote {
            $note = $this->noteRepository->findByIdentifierOrFail($identifier, withTrashed: true);
            if (! $note->trashed()) {
                throw new ApiException('Note is not archived.', 422);
            }
            $note->restore();
            $restored = $this->noteRepository->updateNote($note, [
                'status' => CustomerNoteStatus::Active->value,
                'updated_by' => $actor->id,
            ]);
            event(new CustomerNoteRestored($restored, $actor));

            return $restored;
        });
    }

    public function timeline(string $identifier, int $limit = 50): Collection
    {
        return $this->noteRepository->timeline($this->find($identifier), $limit);
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
}
