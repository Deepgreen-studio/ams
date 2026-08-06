<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\PrivacyRequestStatus;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PrivacyRequestRepository extends BaseRepository
{
    public function __construct(PrivacyRequest $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?PrivacyRequest
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var PrivacyRequest|null $request */
        $request = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('request_number', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $request;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): PrivacyRequest
    {
        $request = $this->findByIdentifier($identifier, $withTrashed);

        if (! $request) {
            abort(404, 'Privacy request not found.');
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'company:id,uuid,company_name',
                'customer:id,uuid,first_name,last_name,company_name,email',
                'assignee:id,uuid,full_name,email',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('request_number', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('requester_email', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['statuses']) && is_array($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        if (! empty($filters['request_type'])) {
            $query->where('request_type', $filters['request_type']);
        }

        if (! empty($filters['identity_verification_status'])) {
            $query->where('identity_verification_status', $filters['identity_verification_status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (! empty($filters['decision'])) {
            $query->where('decision', $filters['decision']);
        }

        if (($filters['overdue'] ?? null) === true || ($filters['overdue'] ?? null) === '1') {
            $query->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('status', PrivacyRequestStatus::activeValues());
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'request_number',
            'request_type',
            'requester_name',
            'status',
            'due_date',
            'completed_at',
            'created_at',
            'updated_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRequest(array $data): PrivacyRequest
    {
        /** @var PrivacyRequest $request */
        $request = $this->model->newQuery()->create($data);

        return $request->fresh([
            'company',
            'customer',
            'assignee',
            'creator',
            'updater',
        ]) ?? $request;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRequest(PrivacyRequest $request, array $data): PrivacyRequest
    {
        $request->fill($data);
        $request->save();

        return $request->refresh()->load([
            'company',
            'customer',
            'assignee',
            'identityVerifier',
            'decisionMaker',
            'creator',
            'updater',
        ]);
    }

    public function generateRequestNumber(): string
    {
        $prefix = 'PRV-'.now()->format('Ymd').'-';

        $last = $this->model->newQuery()
            ->withTrashed()
            ->where('request_number', 'like', $prefix.'%')
            ->orderByDesc('request_number')
            ->value('request_number');

        $sequence = 1;

        if (is_string($last) && preg_match('/(\d{5})$/', $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    public function statistics(?int $companyId = null): array
    {
        $base = $this->model->newQuery();

        if ($companyId !== null) {
            $base->where('company_id', $companyId);
        }

        $byStatus = $base->clone()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $byType = $base->clone()
            ->selectRaw('request_type, COUNT(*) as aggregate')
            ->groupBy('request_type')
            ->pluck('aggregate', 'request_type')
            ->all();

        return [
            'total' => (clone $base)->count(),
            'submitted' => (clone $base)->where('status', PrivacyRequestStatus::Submitted->value)->count(),
            'identity_pending' => (clone $base)->where('status', PrivacyRequestStatus::IdentityPending->value)->count(),
            'under_review' => (clone $base)->where('status', PrivacyRequestStatus::UnderReview->value)->count(),
            'approved' => (clone $base)->where('status', PrivacyRequestStatus::Approved->value)->count(),
            'in_progress' => (clone $base)->where('status', PrivacyRequestStatus::InProgress->value)->count(),
            'completed' => (clone $base)->where('status', PrivacyRequestStatus::Completed->value)->count(),
            'rejected' => (clone $base)->where('status', PrivacyRequestStatus::Rejected->value)->count(),
            'cancelled' => (clone $base)->where('status', PrivacyRequestStatus::Cancelled->value)->count(),
            'active' => (clone $base)->whereIn('status', PrivacyRequestStatus::activeValues())->count(),
            'overdue' => (clone $base)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('status', PrivacyRequestStatus::activeValues())
                ->count(),
            'awaiting_verification' => (clone $base)
                ->where('identity_verification_status', 'pending')
                ->whereIn('status', PrivacyRequestStatus::activeValues())
                ->count(),
            'unassigned' => (clone $base)
                ->whereNull('assigned_to')
                ->whereIn('status', PrivacyRequestStatus::activeValues())
                ->count(),
            'by_status' => array_map('intval', $byStatus),
            'by_type' => array_map('intval', $byType),
        ];
    }

    /**
     * @return Collection<int, PrivacyRequest>
     */
    public function recentActive(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email'])
            ->whereIn('status', PrivacyRequestStatus::activeValues())
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, PrivacyRequest>
     */
    public function awaitingVerification(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email'])
            ->where('identity_verification_status', 'pending')
            ->whereIn('status', PrivacyRequestStatus::activeValues())
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }
}
