<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\ConsentStatus;
use App\Domains\Compliance\Models\UserConsent;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserConsentRepository extends BaseRepository
{
    public function __construct(UserConsent $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?UserConsent
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var UserConsent|null $consent */
        $consent = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $consent;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): UserConsent
    {
        $consent = $this->findByIdentifier($identifier, $withTrashed);

        if (! $consent) {
            abort(404, 'User consent not found.');
        }

        return $consent;
    }

    public function findActiveForSubject(
        int $companyId,
        int $consentTypeId,
        ?int $userId = null,
        ?int $customerId = null,
        ?string $subjectEmail = null
    ): ?UserConsent {
        $query = $this->model->newQuery()
            ->where('company_id', $companyId)
            ->where('consent_type_id', $consentTypeId);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($customerId !== null) {
            $query->where('customer_id', $customerId);
        } elseif (! blank($subjectEmail)) {
            $query->whereNull('user_id')
                ->whereNull('customer_id')
                ->where('subject_email', strtolower($subjectEmail));
        } else {
            return null;
        }

        /** @var UserConsent|null $consent */
        $consent = $query->latest('id')->first();

        return $consent;
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
                'consentType:id,uuid,code,name,channel,current_version',
                'user:id,uuid,full_name,email',
                'customer:id,uuid,first_name,last_name,company_name,email',
                'creator:id,uuid,full_name,email',
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

        if (! empty($filters['consent_type_id'])) {
            $query->where('consent_type_id', (int) $filters['consent_type_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('granted', $filters) && $filters['granted'] !== '' && $filters['granted'] !== null) {
            $query->where('granted', filter_var($filters['granted'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['channel'])) {
            $query->whereHas('consentType', function (Builder $builder) use ($filters): void {
                $builder->where('channel', $filters['channel']);
            });
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('subject_email', 'like', "%{$search}%")
                    ->orWhere('subject_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('device', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'consented_at',
            'withdrawn_at',
            'status',
            'consent_version',
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
    public function createConsent(array $data): UserConsent
    {
        /** @var UserConsent $consent */
        $consent = $this->model->newQuery()->create($data);

        return $consent->fresh([
            'company',
            'consentType',
            'user',
            'customer',
            'creator',
            'updater',
        ]) ?? $consent;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateConsent(UserConsent $consent, array $data): UserConsent
    {
        $consent->fill($data);
        $consent->save();

        return $consent->refresh()->load([
            'company',
            'consentType',
            'user',
            'customer',
            'creator',
            'updater',
        ]);
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

        $bySource = $base->clone()
            ->selectRaw('source, COUNT(*) as aggregate')
            ->groupBy('source')
            ->pluck('aggregate', 'source')
            ->all();

        return [
            'total' => (clone $base)->count(),
            'granted' => (clone $base)->where('status', ConsentStatus::Granted->value)->count(),
            'withdrawn' => (clone $base)->where('status', ConsentStatus::Withdrawn->value)->count(),
            'pending' => (clone $base)->where('status', ConsentStatus::Pending->value)->count(),
            'expired' => (clone $base)->where('status', ConsentStatus::Expired->value)->count(),
            'active_granted' => (clone $base)->where('granted', true)->where('status', ConsentStatus::Granted->value)->count(),
            'by_status' => array_map('intval', $byStatus),
            'by_source' => array_map('intval', $bySource),
        ];
    }

    /**
     * @return Collection<int, UserConsent>
     */
    public function recent(?int $companyId = null, int $limit = 8): Collection
    {
        $query = $this->model->newQuery()
            ->with([
                'consentType:id,uuid,code,name,channel',
                'company:id,uuid,company_name',
            ])
            ->orderByDesc('updated_at')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * Preference center snapshot for a subject.
     *
     * @return Collection<int, UserConsent>
     */
    public function forSubjectPreferences(
        int $companyId,
        ?int $userId = null,
        ?int $customerId = null,
        ?string $subjectEmail = null
    ): Collection {
        $query = $this->model->newQuery()
            ->with('consentType')
            ->where('company_id', $companyId);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($customerId !== null) {
            $query->where('customer_id', $customerId);
        } elseif (! blank($subjectEmail)) {
            $query->where('subject_email', strtolower($subjectEmail));
        } else {
            return collect();
        }

        return $query->orderByDesc('updated_at')->get();
    }
}
