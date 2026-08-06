<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\Subscription;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SubscriptionRepository extends BaseRepository
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Subscription
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Subscription|null $subscription */
        $subscription = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $subscription;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Subscription
    {
        $subscription = $this->findByIdentifier($identifier, $withTrashed);

        if (! $subscription) {
            abort(404, 'Subscription not found.');
        }

        return $subscription;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'customer:id,uuid,first_name,last_name,company_name,email,customer_type,status',
                'customerApplication:id,uuid,application_id,status,ownership_type',
                'customerApplication.application:id,uuid,name,slug,platform,status',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->withCount('licenses')
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

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('plan_name', 'like', "%{$search}%")
                    ->orWhere('external_subscription_id', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['plan_type'])) {
            $query->where('plan_type', $filters['plan_type']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'plan_name', 'plan_type', 'status', 'payment_status', 'starts_at', 'expires_at', 'renews_at', 'created_at', 'updated_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return array<string, int>
     */
    public function statistics(?int $customerId = null): array
    {
        $base = $this->model->newQuery();

        if ($customerId !== null) {
            $base->where('customer_id', $customerId);
        }

        $soon = now()->addDays((int) config('billing.renewal_reminder_days', 14));

        return [
            'total' => (clone $base)->count(),
            'trialing' => (clone $base)->where('status', 'trialing')->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'past_due' => (clone $base)->where('status', 'past_due')->count(),
            'expired' => (clone $base)->where('status', 'expired')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            'paid' => (clone $base)->where('payment_status', 'paid')->count(),
            'pending_payment' => (clone $base)->whereIn('payment_status', ['pending', 'past_due', 'failed'])->count(),
            'renewal_due_soon' => (clone $base)
                ->whereIn('status', ['active', 'trialing', 'past_due'])
                ->where(function (Builder $builder) use ($soon): void {
                    $builder->whereBetween('renews_at', [now(), $soon])
                        ->orWhere(function (Builder $inner) use ($soon): void {
                            $inner->whereNull('renews_at')->whereBetween('expires_at', [now(), $soon]);
                        });
                })
                ->count(),
        ];
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function renewalReminders(?int $customerId = null, int $limit = 20): Collection
    {
        $soon = now()->addDays((int) config('billing.renewal_reminder_days', 14));
        $query = $this->model->newQuery()
            ->with([
                'customer:id,uuid,first_name,last_name,company_name,email',
                'customerApplication.application:id,uuid,name,slug',
            ])
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->where(function (Builder $builder) use ($soon): void {
                $builder->whereBetween('renews_at', [now(), $soon])
                    ->orWhere(function (Builder $inner) use ($soon): void {
                        $inner->whereNull('renews_at')->whereBetween('expires_at', [now(), $soon]);
                    });
            })
            ->orderByRaw('COALESCE(renews_at, expires_at) asc')
            ->limit($limit);

        if ($customerId !== null) {
            $query->where('customer_id', $customerId);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSubscription(array $data): Subscription
    {
        /** @var Subscription $subscription */
        $subscription = $this->model->newQuery()->create($data);

        return $subscription->fresh([
            'customer',
            'customerApplication.application',
            'licenses',
            'creator',
            'updater',
        ]) ?? $subscription;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSubscription(Subscription $subscription, array $data): Subscription
    {
        $subscription->fill($data);
        $subscription->save();

        return $subscription->refresh()->load([
            'customer',
            'customerApplication.application',
            'licenses',
            'creator',
            'updater',
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(Subscription $subscription, int $limit = 50): Collection
    {
        $activityModel = config('activitylog.activity_model');

        return $activityModel::query()
            ->forSubject($subscription)
            ->with(['causer:id,uuid,full_name,email'])
            ->latest()
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(static function ($activity): array {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'log_name' => $activity->log_name,
                    'created_at' => $activity->created_at,
                    'properties' => $activity->properties,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'uuid' => $activity->causer->uuid,
                        'full_name' => $activity->causer->full_name,
                        'email' => $activity->causer->email,
                    ] : null,
                ];
            });
    }
}
