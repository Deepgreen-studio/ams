<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Models\SupportSlaPolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupportSlaPolicyRepository
{
    public function findByIdentifierOrFail(string $identifier): SupportSlaPolicy
    {
        return SupportSlaPolicy::query()
            ->with(['calendar', 'company:id,uuid,company_name', 'escalationRules'])
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = SupportSlaPolicy::query()->with([
            'company:id,uuid,company_name',
            'calendar:id,uuid,name,timezone',
            'escalationRules',
        ]);

        if (! blank($filters['company_id'] ?? null)) {
            $query->where(function (Builder $builder) use ($filters): void {
                $builder->where('company_id', $filters['company_id'])
                    ->orWhereNull('company_id');
            });
        }

        if (($filters['scope'] ?? null) === 'global') {
            $query->whereNull('company_id');
        }

        if (($filters['scope'] ?? null) === 'company' && ! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! blank($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        return $query->orderByDesc('is_default')->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function resolveForTicket(int $companyId, ?string $priority, ?string $category): ?SupportSlaPolicy
    {
        $candidates = SupportSlaPolicy::query()
            ->with('calendar')
            ->where('is_active', true)
            ->where(function (Builder $query) use ($companyId): void {
                $query->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->get();

        $rank = static function (SupportSlaPolicy $policy) use ($companyId, $priority, $category): int {
            $score = 0;
            if ((int) $policy->company_id === $companyId) {
                $score += 100;
            }
            $policyPriority = $policy->priority?->value ?? $policy->priority;
            $policyCategory = $policy->category?->value ?? $policy->category;

            if ($policyPriority !== null && $policyPriority === $priority) {
                $score += 50;
            } elseif ($policyPriority === null) {
                $score += 10;
            } else {
                return -1;
            }

            if ($policyCategory !== null && $policyCategory === $category) {
                $score += 25;
            } elseif ($policyCategory === null) {
                $score += 5;
            } else {
                return -1;
            }

            if ($policy->is_default) {
                $score += 1;
            }

            return $score;
        };

        return $candidates
            ->map(fn (SupportSlaPolicy $policy) => ['policy' => $policy, 'score' => $rank($policy)])
            ->filter(fn (array $row) => $row['score'] >= 0)
            ->sortByDesc('score')
            ->pluck('policy')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupportSlaPolicy
    {
        return SupportSlaPolicy::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupportSlaPolicy $policy, array $data): SupportSlaPolicy
    {
        $policy->fill($data);
        $policy->save();

        return $policy->fresh(['calendar', 'company', 'escalationRules']) ?? $policy;
    }
}
