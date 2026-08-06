<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Models\ConsentHistory;
use App\Domains\Compliance\Models\UserConsent;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ConsentHistoryRepository extends BaseRepository
{
    public function __construct(ConsentHistory $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): ConsentHistory
    {
        /** @var ConsentHistory $history */
        $history = $this->model->newQuery()->create($data);

        return $history->load([
            'actor:id,uuid,full_name,email',
            'consentType:id,uuid,code,name,channel',
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordForConsent(
        UserConsent $consent,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?bool $fromGranted,
        ?bool $toGranted,
        ?string $fromVersion,
        ?string $toVersion,
        ?int $actorId,
        ?string $comments = null,
        array $context = [],
        array $metadata = []
    ): ConsentHistory {
        return $this->record([
            'user_consent_id' => $consent->id,
            'consent_type_id' => $consent->consent_type_id,
            'company_id' => $consent->company_id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'from_granted' => $fromGranted,
            'to_granted' => $toGranted,
            'ip_address' => $context['ip_address'] ?? $consent->ip_address,
            'device' => $context['device'] ?? $consent->device,
            'source' => $context['source'] ?? ($consent->source?->value ?? $consent->source),
            'acted_by' => $actorId,
            'comments' => $comments,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    /**
     * @return Collection<int, ConsentHistory>
     */
    public function forConsent(int $userConsentId): Collection
    {
        return $this->model->newQuery()
            ->with([
                'actor:id,uuid,full_name,email',
                'consentType:id,uuid,code,name,channel',
            ])
            ->where('user_consent_id', $userConsentId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = $this->model->newQuery()
            ->with([
                'actor:id,uuid,full_name,email',
                'consentType:id,uuid,code,name,channel',
                'userConsent:id,uuid,subject_email,subject_name,status,granted',
                'company:id,uuid,company_name',
            ]);

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['consent_type_id'])) {
            $query->where('consent_type_id', (int) $filters['consent_type_id']);
        }

        if (! empty($filters['user_consent_id'])) {
            $query->where('user_consent_id', (int) $filters['user_consent_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('comments', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('device', 'like', "%{$search}%")
                    ->orWhereHas('userConsent', function (Builder $consentQuery) use ($search): void {
                        $consentQuery->where('subject_email', 'like', "%{$search}%")
                            ->orWhere('subject_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
