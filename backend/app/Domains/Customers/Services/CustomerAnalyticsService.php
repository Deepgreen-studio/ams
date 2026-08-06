<?php

namespace App\Domains\Customers\Services;

use App\Domains\Applications\Models\ApplicationAnalyticsDaily;
use App\Domains\Customers\Enums\CustomerRiskLevel;
use App\Domains\Customers\Enums\SubscriptionStatus;
use App\Domains\Customers\Events\CustomerAnalyticsSnapshotComputed;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use App\Domains\Customers\Models\CustomerApplication;
use App\Domains\Customers\Models\CustomerCommunication;
use App\Domains\Customers\Models\CustomerDocument;
use App\Domains\Customers\Models\CustomerNote;
use App\Domains\Customers\Models\CustomerTask;
use App\Domains\Customers\Models\Subscription;
use App\Domains\Customers\Repositories\CustomerAnalyticsSnapshotRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class CustomerAnalyticsService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerAnalyticsSnapshotRepository $snapshotRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $customerIdentifier, ?string $from = null, ?string $to = null): array
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);
        $range = $this->resolveRange($from, $to, 30);

        $history = $this->ensureHistory($customer, $range['from'], $range['to']);
        $latest = $this->refreshSnapshot($customer, now(), null);
        $history = $history
            ->reject(fn (CustomerAnalyticsSnapshot $row) => optional($row->snapshot_date)->toDateString() === optional($latest->snapshot_date)->toDateString())
            ->push($latest)
            ->sortBy(fn (CustomerAnalyticsSnapshot $row) => optional($row->snapshot_date)->toDateString())
            ->values();

        return [
            'customer' => [
                'uuid' => $customer->uuid,
                'display_name' => $customer->display_name,
                'email' => $customer->email,
                'status' => $customer->status?->value ?? $customer->status,
            ],
            'current' => $this->snapshotPayload($latest),
            'risk_indicators' => $this->riskIndicators($latest, $customer),
            'usage_report' => $this->usageReport($latest),
            'charts' => $this->charts($history),
            'growth' => $this->growthTrends($history),
            'timeline' => $this->timeline($customer, 25),
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function health(string $customerIdentifier): array
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);
        $snapshot = $this->refreshSnapshot($customer, now(), null);

        return [
            'health_score' => $snapshot->health_score,
            'activity_score' => $snapshot->activity_score,
            'risk_level' => $snapshot->risk_level?->value ?? $snapshot->risk_level,
            'risk_indicators' => $this->riskIndicators($snapshot, $customer),
            'subscription_status' => $snapshot->subscription_status,
            'subscription_active' => $snapshot->subscription_active,
            'computed_at' => $snapshot->computed_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function trends(string $customerIdentifier, ?string $from = null, ?string $to = null): array
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);
        $range = $this->resolveRange($from, $to, 30);
        $history = $this->ensureHistory($customer, $range['from'], $range['to']);

        return [
            'charts' => $this->charts($history),
            'growth' => $this->growthTrends($history),
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function usage(string $customerIdentifier): array
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);
        $snapshot = $this->refreshSnapshot($customer, now(), null);

        return $this->usageReport($snapshot);
    }

    public function refresh(string $customerIdentifier, ?User $actor = null): CustomerAnalyticsSnapshot
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);

        return $this->refreshSnapshot($customer, now(), $actor);
    }

    public function refreshSnapshot(Customer $customer, Carbon $date, ?User $actor = null): CustomerAnalyticsSnapshot
    {
        $metrics = $this->collectMetrics($customer, $date->copy()->startOfDay());
        $scores = $this->scoreCustomer($metrics);

        $snapshot = $this->snapshotRepository->upsertForDate($customer->id, $date->copy()->startOfDay(), [
            'applications_total' => $metrics['applications_total'],
            'applications_active' => $metrics['applications_active'],
            'integrations_total' => $metrics['integrations_total'],
            'api_usage_count' => $metrics['api_usage_count'],
            'login_activity_count' => $metrics['login_activity_count'],
            'support_tickets_open' => $metrics['support_tickets_open'],
            'support_tickets_total' => $metrics['support_tickets_total'],
            'subscription_status' => $metrics['subscription_status'],
            'subscription_active' => $metrics['subscription_active'],
            'health_score' => $scores['health_score'],
            'activity_score' => $scores['activity_score'],
            'risk_level' => $scores['risk_level'],
            'metrics' => $metrics['meta'],
        ]);

        if ($actor) {
            event(new CustomerAnalyticsSnapshotComputed($snapshot, $actor));
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectMetrics(Customer $customer, Carbon $day): array
    {
        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();
        $windowStart = $day->copy()->subDays(29)->startOfDay();

        $assignments = CustomerApplication::query()
            ->where('customer_id', $customer->id)
            ->get(['id', 'application_id', 'integration_id', 'status']);

        $applicationsTotal = $assignments->count();
        $applicationsActive = $assignments->filter(
            fn ($assignment) => ($assignment->status?->value ?? $assignment->status) === 'active'
        )->count();
        $integrationsTotal = $assignments->pluck('integration_id')->filter()->unique()->count();
        $applicationIds = $assignments->pluck('application_id')->filter()->unique()->values();

        $apiUsage = 0;
        if ($applicationIds->isNotEmpty()) {
            $apiUsage = (int) ApplicationAnalyticsDaily::query()
                ->whereIn('application_id', $applicationIds)
                ->whereDate('metric_date', $dayStart->toDateString())
                ->sum('sessions');
        }

        $noteIds = CustomerNote::query()->where('customer_id', $customer->id)->pluck('id');
        $taskIds = CustomerTask::query()->where('customer_id', $customer->id)->pluck('id');
        $commIds = CustomerCommunication::query()->where('customer_id', $customer->id)->pluck('id');
        $docIds = CustomerDocument::query()->where('customer_id', $customer->id)->pluck('id');
        $subIds = Subscription::query()->where('customer_id', $customer->id)->pluck('id');

        $loginActivity = Activity::query()
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->where(function ($query) use ($noteIds, $taskIds, $commIds, $docIds, $subIds, $customer): void {
                $query->where(function ($builder) use ($customer): void {
                    $builder->where('subject_type', Customer::class)
                        ->where('subject_id', $customer->id);
                })->orWhere(function ($builder) use ($noteIds): void {
                    $builder->where('subject_type', CustomerNote::class)->whereIn('subject_id', $noteIds);
                })->orWhere(function ($builder) use ($taskIds): void {
                    $builder->where('subject_type', CustomerTask::class)->whereIn('subject_id', $taskIds);
                })->orWhere(function ($builder) use ($commIds): void {
                    $builder->where('subject_type', CustomerCommunication::class)->whereIn('subject_id', $commIds);
                })->orWhere(function ($builder) use ($docIds): void {
                    $builder->where('subject_type', CustomerDocument::class)->whereIn('subject_id', $docIds);
                })->orWhere(function ($builder) use ($subIds): void {
                    $builder->where('subject_type', Subscription::class)->whereIn('subject_id', $subIds);
                });
            })
            ->count();

        // Support tickets module is future; open customer tasks + period communications act as interim support signal.
        $supportOpen = CustomerTask::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $supportTotal = CustomerTask::query()->where('customer_id', $customer->id)->count()
            + CustomerCommunication::query()
                ->where('customer_id', $customer->id)
                ->whereIn('type', ['email', 'call'])
                ->whereBetween('occurred_at', [$windowStart, $dayEnd])
                ->count();

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();

        $subscriptionStatus = $subscription?->status?->value ?? $subscription?->status;
        $subscriptionActive = in_array($subscriptionStatus, [
            SubscriptionStatus::Active->value,
            SubscriptionStatus::Trialing->value,
        ], true);

        $overdueTasks = CustomerTask::query()
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $notesRecent = CustomerNote::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('created_at', [$windowStart, $dayEnd])
            ->count();

        $commsRecent = CustomerCommunication::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('occurred_at', [$windowStart, $dayEnd])
            ->count();

        $docsCurrent = CustomerDocument::query()
            ->where('customer_id', $customer->id)
            ->where('is_current', true)
            ->count();

        $renewalDueSoon = false;
        if ($subscription && ($subscription->renews_at || $subscription->expires_at)) {
            $target = $subscription->renews_at ?? $subscription->expires_at;
            $days = (int) ($subscription->renewal_reminder_days ?: 14);
            $renewalDueSoon = $target->betweenIncluded(now(), now()->addDays($days));
        }

        return [
            'applications_total' => $applicationsTotal,
            'applications_active' => $applicationsActive,
            'integrations_total' => $integrationsTotal,
            'api_usage_count' => $apiUsage,
            'login_activity_count' => $loginActivity,
            'support_tickets_open' => $supportOpen,
            'support_tickets_total' => $supportTotal,
            'subscription_status' => $subscriptionStatus,
            'subscription_active' => $subscriptionActive,
            'meta' => [
                'overdue_tasks' => $overdueTasks,
                'notes_recent' => $notesRecent,
                'communications_recent' => $commsRecent,
                'documents_current' => $docsCurrent,
                'renewal_due_soon' => $renewalDueSoon,
                'payment_status' => $subscription?->payment_status?->value ?? $subscription?->payment_status,
                'sources' => [
                    'support_tickets' => 'proxied_from_open_tasks_and_communications',
                    'api_usage' => 'assigned_application_daily_sessions',
                    'login_activity' => 'customer_domain_activity_log_events',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{health_score: int, activity_score: int, risk_level: string}
     */
    protected function scoreCustomer(array $metrics): array
    {
        $health = 45;
        $meta = $metrics['meta'] ?? [];

        if ($metrics['subscription_active']) {
            $health += 18;
        }
        if (in_array($metrics['subscription_status'], [
            SubscriptionStatus::PastDue->value,
            SubscriptionStatus::Expired->value,
            SubscriptionStatus::Suspended->value,
            SubscriptionStatus::Cancelled->value,
        ], true)) {
            $health -= 22;
        }
        if ($metrics['applications_active'] > 0) {
            $health += 12;
        } elseif ($metrics['applications_total'] === 0) {
            $health -= 12;
        }
        if ($metrics['integrations_total'] > 0) {
            $health += 8;
        }
        if (($meta['renewal_due_soon'] ?? false) && ! $metrics['subscription_active']) {
            $health -= 8;
        }
        if (($meta['overdue_tasks'] ?? 0) > 0) {
            $health -= min(15, (int) $meta['overdue_tasks'] * 3);
        }
        if ($metrics['support_tickets_open'] >= 5) {
            $health -= 10;
        } elseif ($metrics['support_tickets_open'] >= 2) {
            $health -= 5;
        }

        $activityRaw = ($meta['notes_recent'] ?? 0) * 4
            + ($meta['communications_recent'] ?? 0) * 5
            + $metrics['login_activity_count'] * 3
            + min(30, (int) floor($metrics['api_usage_count'] / 50))
            + ($metrics['applications_active'] * 6)
            + (($meta['documents_current'] ?? 0) * 2);

        $activity = (int) max(0, min(100, $activityRaw));
        $health += (int) floor($activity / 20);
        $health = (int) max(0, min(100, $health));

        $risk = match (true) {
            $health < 40 || in_array($metrics['subscription_status'], [
                SubscriptionStatus::PastDue->value,
                SubscriptionStatus::Expired->value,
            ], true) => CustomerRiskLevel::Critical->value,
            $health < 55 || ($meta['overdue_tasks'] ?? 0) >= 3 => CustomerRiskLevel::High->value,
            $health < 70 => CustomerRiskLevel::Medium->value,
            default => CustomerRiskLevel::Low->value,
        };

        return [
            'health_score' => $health,
            'activity_score' => $activity,
            'risk_level' => $risk,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function riskIndicators(CustomerAnalyticsSnapshot $snapshot, Customer $customer): array
    {
        $meta = $snapshot->metrics ?? [];
        $indicators = [];

        if (! $snapshot->subscription_active) {
            $indicators[] = [
                'code' => 'subscription_inactive',
                'severity' => 'high',
                'label' => 'No active subscription',
                'detail' => 'Current status: '.($snapshot->subscription_status ?: 'none'),
            ];
        }

        if (in_array($snapshot->subscription_status, [
            SubscriptionStatus::PastDue->value,
            SubscriptionStatus::Expired->value,
        ], true)) {
            $indicators[] = [
                'code' => 'billing_risk',
                'severity' => 'critical',
                'label' => 'Billing / subscription risk',
                'detail' => 'Subscription is '.$snapshot->subscription_status,
            ];
        }

        if (($meta['renewal_due_soon'] ?? false) === true) {
            $indicators[] = [
                'code' => 'renewal_due_soon',
                'severity' => 'medium',
                'label' => 'Renewal due soon',
                'detail' => 'Subscription renews or expires within the reminder window.',
            ];
        }

        if ($snapshot->applications_active === 0) {
            $indicators[] = [
                'code' => 'no_active_apps',
                'severity' => 'medium',
                'label' => 'No active applications',
                'detail' => 'Customer has no active application assignments.',
            ];
        }

        if (($meta['overdue_tasks'] ?? 0) > 0) {
            $indicators[] = [
                'code' => 'overdue_tasks',
                'severity' => 'high',
                'label' => 'Overdue tasks',
                'detail' => ($meta['overdue_tasks']).' open task(s) past due date.',
            ];
        }

        if ($snapshot->support_tickets_open >= 3) {
            $indicators[] = [
                'code' => 'support_load',
                'severity' => 'medium',
                'label' => 'Elevated support load',
                'detail' => $snapshot->support_tickets_open.' open support items (tasks proxy until Support module).',
            ];
        }

        if ($snapshot->activity_score < 25) {
            $indicators[] = [
                'code' => 'low_engagement',
                'severity' => 'medium',
                'label' => 'Low engagement',
                'detail' => 'Activity score is '.$snapshot->activity_score.'.',
            ];
        }

        if ($indicators === []) {
            $indicators[] = [
                'code' => 'healthy',
                'severity' => 'low',
                'label' => 'No major risks detected',
                'detail' => 'Customer health looks stable for '.$customer->display_name.'.',
            ];
        }

        return $indicators;
    }

    /**
     * @return array<string, mixed>
     */
    protected function usageReport(CustomerAnalyticsSnapshot $snapshot): array
    {
        return [
            'applications_total' => $snapshot->applications_total,
            'applications_active' => $snapshot->applications_active,
            'integrations_total' => $snapshot->integrations_total,
            'api_usage_count' => $snapshot->api_usage_count,
            'login_activity_count' => $snapshot->login_activity_count,
            'support_tickets_open' => $snapshot->support_tickets_open,
            'support_tickets_total' => $snapshot->support_tickets_total,
            'documents_current' => $snapshot->metrics['documents_current'] ?? 0,
            'notes_recent' => $snapshot->metrics['notes_recent'] ?? 0,
            'communications_recent' => $snapshot->metrics['communications_recent'] ?? 0,
            'sources' => $snapshot->metrics['sources'] ?? [],
        ];
    }

    /**
     * @param  Collection<int, CustomerAnalyticsSnapshot>|iterable<int, CustomerAnalyticsSnapshot>  $history
     * @return array<string, mixed>
     */
    protected function charts(iterable $history): array
    {
        $rows = collect($history);

        return [
            'labels' => $rows->map(fn (CustomerAnalyticsSnapshot $row) => optional($row->snapshot_date)->toDateString())->values()->all(),
            'health_score' => $rows->pluck('health_score')->values()->all(),
            'activity_score' => $rows->pluck('activity_score')->values()->all(),
            'api_usage_count' => $rows->pluck('api_usage_count')->values()->all(),
            'login_activity_count' => $rows->pluck('login_activity_count')->values()->all(),
            'applications_active' => $rows->pluck('applications_active')->values()->all(),
            'support_tickets_open' => $rows->pluck('support_tickets_open')->values()->all(),
        ];
    }

    /**
     * @param  iterable<int, CustomerAnalyticsSnapshot>  $history
     * @return array<string, mixed>
     */
    protected function growthTrends(iterable $history): array
    {
        $rows = collect($history);
        if ($rows->count() < 2) {
            return [
                'health_delta' => 0,
                'activity_delta' => 0,
                'api_usage_delta' => 0,
                'applications_delta' => 0,
                'direction' => 'flat',
            ];
        }

        $first = $rows->first();
        $last = $rows->last();
        $healthDelta = (int) $last->health_score - (int) $first->health_score;
        $activityDelta = (int) $last->activity_score - (int) $first->activity_score;

        return [
            'health_delta' => $healthDelta,
            'activity_delta' => $activityDelta,
            'api_usage_delta' => (int) $last->api_usage_count - (int) $first->api_usage_count,
            'applications_delta' => (int) $last->applications_active - (int) $first->applications_active,
            'direction' => $healthDelta > 2 ? 'up' : ($healthDelta < -2 ? 'down' : 'flat'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function timeline(Customer $customer, int $limit = 25): array
    {
        $noteIds = CustomerNote::query()->where('customer_id', $customer->id)->pluck('id');
        $taskIds = CustomerTask::query()->where('customer_id', $customer->id)->pluck('id');
        $commIds = CustomerCommunication::query()->where('customer_id', $customer->id)->pluck('id');

        return Activity::query()
            ->where(function ($query) use ($noteIds, $taskIds, $commIds, $customer): void {
                $query->where(function ($builder) use ($customer): void {
                    $builder->where('subject_type', Customer::class)->where('subject_id', $customer->id);
                })->orWhere(function ($builder) use ($noteIds): void {
                    $builder->where('subject_type', CustomerNote::class)->whereIn('subject_id', $noteIds);
                })->orWhere(function ($builder) use ($taskIds): void {
                    $builder->where('subject_type', CustomerTask::class)->whereIn('subject_id', $taskIds);
                })->orWhere(function ($builder) use ($commIds): void {
                    $builder->where('subject_type', CustomerCommunication::class)->whereIn('subject_id', $commIds);
                });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (Activity $item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'event' => $item->properties['event'] ?? null,
                'subject_type' => class_basename((string) $item->subject_type),
                'created_at' => $item->created_at,
            ])
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, CustomerAnalyticsSnapshot>
     */
    protected function ensureHistory(Customer $customer, Carbon $from, Carbon $to)
    {
        // Cap automatic backfill so first dashboard load stays performant.
        $seedFrom = $from->copy();
        if ($seedFrom->diffInDays($to) > 14) {
            $seedFrom = $to->copy()->subDays(13)->startOfDay();
        }

        $existing = $this->snapshotRepository->forRange($customer->id, $seedFrom, $to)->keyBy(
            fn (CustomerAnalyticsSnapshot $row) => optional($row->snapshot_date)->toDateString()
        );

        $cursor = $seedFrom->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            if (! $existing->has($key)) {
                $snapshot = $this->refreshSnapshot($customer, $cursor->copy(), null);
                $existing->put($key, $snapshot);
            }
            $cursor->addDay();
        }

        return $existing->sortKeys()->values();
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    protected function resolveRange(?string $from, ?string $to, int $defaultDays): array
    {
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : $toDate->copy()->subDays($defaultDays - 1)->startOfDay();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        return ['from' => $fromDate, 'to' => $toDate];
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshotPayload(CustomerAnalyticsSnapshot $snapshot): array
    {
        return [
            'uuid' => $snapshot->uuid,
            'snapshot_date' => optional($snapshot->snapshot_date)->toDateString(),
            'applications_total' => $snapshot->applications_total,
            'applications_active' => $snapshot->applications_active,
            'integrations_total' => $snapshot->integrations_total,
            'api_usage_count' => $snapshot->api_usage_count,
            'login_activity_count' => $snapshot->login_activity_count,
            'support_tickets_open' => $snapshot->support_tickets_open,
            'support_tickets_total' => $snapshot->support_tickets_total,
            'subscription_status' => $snapshot->subscription_status,
            'subscription_active' => $snapshot->subscription_active,
            'health_score' => $snapshot->health_score,
            'activity_score' => $snapshot->activity_score,
            'risk_level' => $snapshot->risk_level?->value ?? $snapshot->risk_level,
            'metrics' => $snapshot->metrics,
            'computed_at' => $snapshot->computed_at,
        ];
    }
}
