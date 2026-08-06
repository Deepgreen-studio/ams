<?php

namespace App\Domains\Notifications\Repositories;

use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NotificationTemplateRepository extends BaseRepository
{
    public function __construct(NotificationTemplate $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?NotificationTemplate
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var NotificationTemplate|null $template */
        $template = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $template;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): NotificationTemplate
    {
        $template = $this->findByIdentifier($identifier, $withTrashed);

        if (! $template) {
            abort(404, 'Notification template not found.');
        }

        return $template;
    }

    public function resolveActive(string $eventKey, string $channel, string $locale = 'en'): ?NotificationTemplate
    {
        $base = fn (string $localeValue) => $this->model->newQuery()
            ->where('event_key', $eventKey)
            ->where('channel', $channel)
            ->where('locale', $localeValue)
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('workflow_status', NotificationTemplateStatus::Published->value)
                    ->orWhere('is_system', true);
            })
            ->orderByDesc('is_system')
            ->orderByRaw("CASE WHEN workflow_status = 'published' THEN 0 ELSE 1 END")
            ->orderBy('id');

        /** @var NotificationTemplate|null $template */
        $template = $base($locale)->first();

        if ($template || $locale === 'en') {
            return $template;
        }

        /** @var NotificationTemplate|null $fallback */
        $fallback = $base('en')->first();

        return $fallback;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = $this->model->newQuery()
            ->with(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email'])
            ->orderBy('event_key')
            ->orderBy('channel')
            ->orderBy('locale');

        if (! blank($filters['event_key'] ?? null)) {
            $query->where('event_key', $filters['event_key']);
        }
        if (! blank($filters['channel'] ?? null)) {
            $query->where('channel', $filters['channel']);
        }
        if (! blank($filters['locale'] ?? null)) {
            $query->where('locale', $filters['locale']);
        }
        if (! blank($filters['workflow_status'] ?? null)) {
            $query->where('workflow_status', $filters['workflow_status']);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }
        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
