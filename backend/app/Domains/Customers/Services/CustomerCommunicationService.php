<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Enums\CustomerCommunicationDirection;
use App\Domains\Customers\Enums\CustomerCommunicationStatus;
use App\Domains\Customers\Enums\CustomerCommunicationType;
use App\Domains\Customers\Events\CustomerCommunicationCreated;
use App\Domains\Customers\Events\CustomerCommunicationDeleted;
use App\Domains\Customers\Events\CustomerCommunicationRestored;
use App\Domains\Customers\Events\CustomerCommunicationUpdated;
use App\Domains\Customers\Models\CustomerCommunication;
use App\Domains\Customers\Repositories\CustomerCommunicationRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerCommunicationService
{
    public function __construct(
        private readonly CustomerCommunicationRepository $communicationRepository,
        private readonly CustomerRepository $customerRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{communications: LengthAwarePaginator, statistics: array<string, int>}
     */
    public function list(array $filters = []): array
    {
        $filters = $this->resolveFilters($filters);
        $customerId = isset($filters['customer_id']) ? (int) $filters['customer_id'] : null;

        return [
            'communications' => $this->communicationRepository->paginateFiltered($filters),
            'statistics' => $this->communicationRepository->statistics($customerId),
        ];
    }

    public function find(string $identifier, bool $withTrashed = false): CustomerCommunication
    {
        return $this->communicationRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): CustomerCommunication
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
    public function create(array $data, User $actor): CustomerCommunication
    {
        return DB::transaction(function () use ($data, $actor): CustomerCommunication {
            $customer = $this->customerRepository->findByIdentifierOrFail((string) $data['customer_id']);
            $participants = $data['participants'] ?? null;
            if (is_string($participants)) {
                $decoded = json_decode($participants, true);
                $participants = is_array($decoded) ? $decoded : null;
            }

            $communication = $this->communicationRepository->createCommunication([
                'customer_id' => $customer->id,
                'type' => $data['type'] ?? CustomerCommunicationType::Email->value,
                'direction' => $data['direction'] ?? CustomerCommunicationDirection::Outbound->value,
                'subject' => $data['subject'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'] ?? CustomerCommunicationStatus::Logged->value,
                'channel_reference' => $data['channel_reference'] ?? null,
                'participants' => $participants,
                'duration_seconds' => $data['duration_seconds'] ?? null,
                'occurred_at' => ! empty($data['occurred_at']) ? Carbon::parse((string) $data['occurred_at']) : now(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new CustomerCommunicationCreated($communication, $actor));

            return $communication->load(['customer:id,uuid,first_name,last_name,company_name,email', 'creator:id,uuid,full_name,email']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): CustomerCommunication
    {
        return DB::transaction(function () use ($identifier, $data, $actor): CustomerCommunication {
            $communication = $this->communicationRepository->findByIdentifierOrFail($identifier);
            $payload = array_intersect_key($data, array_flip([
                'type', 'direction', 'subject', 'body', 'status', 'channel_reference', 'participants', 'duration_seconds', 'occurred_at',
            ]));

            if (array_key_exists('occurred_at', $payload)) {
                $payload['occurred_at'] = blank($payload['occurred_at']) ? now() : Carbon::parse((string) $payload['occurred_at']);
            }
            if (array_key_exists('participants', $payload) && is_string($payload['participants'])) {
                $decoded = json_decode($payload['participants'], true);
                $payload['participants'] = is_array($decoded) ? $decoded : null;
            }
            foreach (['subject', 'body', 'channel_reference'] as $field) {
                if (array_key_exists($field, $payload) && blank($payload[$field])) {
                    $payload[$field] = null;
                }
            }

            $payload['updated_by'] = $actor->id;
            $updated = $this->communicationRepository->updateCommunication($communication, $payload);
            event(new CustomerCommunicationUpdated($updated, $actor));

            return $updated->load(['customer:id,uuid,first_name,last_name,company_name,email', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $communication = $this->communicationRepository->findByIdentifierOrFail($identifier);
            $this->communicationRepository->updateCommunication($communication, ['updated_by' => $actor->id]);
            $communication->delete();
            event(new CustomerCommunicationDeleted($communication, $actor));
        });
    }

    public function restore(string $identifier, User $actor): CustomerCommunication
    {
        return DB::transaction(function () use ($identifier, $actor): CustomerCommunication {
            $communication = $this->communicationRepository->findByIdentifierOrFail($identifier, withTrashed: true);
            if (! $communication->trashed()) {
                throw new ApiException('Communication is not archived.', 422);
            }
            $communication->restore();
            $restored = $this->communicationRepository->updateCommunication($communication, ['updated_by' => $actor->id]);
            event(new CustomerCommunicationRestored($restored, $actor));

            return $restored;
        });
    }

    public function timeline(string $identifier, int $limit = 50): Collection
    {
        return $this->communicationRepository->timeline($this->find($identifier), $limit);
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
