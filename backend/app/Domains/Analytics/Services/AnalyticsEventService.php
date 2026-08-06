<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Events\AnalyticsEventRecorded;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Domains\Analytics\Repositories\AnalyticsEventRepository;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsEventService
{
    public function __construct(
        private readonly AnalyticsEventRepository $eventRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?User $actor = null): AnalyticsEvent
    {
        return DB::transaction(function () use ($data, $actor): AnalyticsEvent {
            $payload = $this->preparePayload($data, $actor);

            /** @var AnalyticsEvent $event */
            $event = $this->eventRepository->create($payload);

            event(new AnalyticsEventRecorded($event, $actor));

            return $event->load(['company', 'user', 'application']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return list<AnalyticsEvent>
     */
    public function recordMany(array $events, ?User $actor = null): array
    {
        return DB::transaction(function () use ($events, $actor): array {
            $created = [];

            foreach ($events as $data) {
                $payload = $this->preparePayload($data, $actor);
                /** @var AnalyticsEvent $event */
                $event = $this->eventRepository->create($payload);
                event(new AnalyticsEventRecorded($event, $actor));
                $created[] = $event;
            }

            return $created;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->eventRepository->paginateFiltered($this->normalizeFilters($filters));
    }

    public function find(string $uuid): AnalyticsEvent
    {
        return $this->eventRepository->findByUuidOrFail($uuid)
            ->load(['company', 'user', 'application', 'customer']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        return [
            'period' => [
                'from' => $normalized['from'] ?? null,
                'to' => $normalized['to'] ?? null,
            ],
            'total' => array_sum($this->eventRepository->countByCategory($normalized)),
            'by_category' => $this->eventRepository->countByCategory($normalized),
            'top_events' => $this->eventRepository->topEventNames($normalized),
            'daily_trend' => $this->eventRepository->dailyTrend($normalized),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, ?User $actor): array
    {
        $companyId = $this->resolveCompanyId($data['company_id'] ?? $data['company'] ?? null);

        return [
            'company_id' => $companyId,
            'user_id' => $data['user_id'] ?? $actor?->id,
            'application_id' => $data['application_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'category' => $data['category'],
            'event_name' => $data['event_name'],
            'event_source' => $data['event_source'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => isset($data['subject_id']) ? (string) $data['subject_id'] : null,
            'properties' => $data['properties'] ?? null,
            'metrics' => $data['metrics'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()?->ip(),
            'user_agent' => $data['user_agent'] ?? request()?->userAgent(),
            'occurred_at' => isset($data['occurred_at'])
                ? Carbon::parse((string) $data['occurred_at'])
                : now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function normalizeFilters(array $filters): array
    {
        if (! empty($filters['company']) && empty($filters['company_id'])) {
            $filters['company_id'] = $this->resolveCompanyId($filters['company']);
        }

        if (empty($filters['from'])) {
            $filters['from'] = now()->subDays(29)->toDateString();
        }

        if (empty($filters['to'])) {
            $filters['to'] = now()->toDateString();
        }

        return $filters;
    }

    protected function resolveCompanyId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $company = Company::query()
            ->where('uuid', (string) $value)
            ->first();

        return $company?->id;
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public function categories(): array
    {
        return array_map(
            fn (AnalyticsCategory $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
                'description' => $category->description(),
            ],
            AnalyticsCategory::cases()
        );
    }
}
