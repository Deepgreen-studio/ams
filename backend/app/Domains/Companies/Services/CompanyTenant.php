<?php

namespace App\Domains\Companies\Services;

use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyTenant
{
    public const ENFORCE = 'company.tenant.enforce';

    /**
     * @var array<int, list<int>|null>
     */
    private static array $cache = [];

    public static function flush(): void
    {
        self::$cache = [];
    }

    public function enforced(): bool
    {
        return app()->bound(self::ENFORCE);
    }

    /**
     * Null means the caller can see every company. An empty list means they can see none.
     *
     * @return list<int>|null
     */
    public function idsFor(?User $user): ?array
    {
        if (! $this->enforced()) {
            return null;
        }

        if ($user === null) {
            return [];
        }

        if (array_key_exists($user->id, self::$cache)) {
            return self::$cache[$user->id];
        }

        if ($user->hasRole('super-admin')) {
            return self::$cache[$user->id] = null;
        }

        return self::$cache[$user->id] = DB::table('company_user')
            ->where('user_id', $user->id)
            ->pluck('company_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function constrain(Builder $query, string $column, bool $shareUnassigned = false): void
    {
        $ids = $this->idsFor(Auth::user());

        if ($ids === null) {
            return;
        }

        $query->where(function (Builder $inner) use ($column, $ids, $shareUnassigned): void {
            $inner->whereIn($column, $ids === [] ? [-1] : $ids);

            if ($shareUnassigned) {
                $inner->orWhereNull($column);
            }
        });
    }

    public function constrainUsers(Builder $query): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            if ($this->enforced()) {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        $ids = $this->idsFor($user);

        if ($ids === null || $ids === []) {
            return;
        }

        $query->where(function (Builder $inner) use ($ids, $user): void {
            $inner->whereHas('companies', function (Builder $companies) use ($ids): void {
                $companies->withoutGlobalScope('company_tenant')
                    ->whereIn('companies.id', $ids);
            })->orWhere('users.id', $user->id);
        });
    }

    public function contentCompanyId(User $actor, mixed $requested): ?int
    {
        $ids = $this->idsFor($actor);
        $companyId = $this->requestedCompanyId($requested);

        if ($companyId !== null && $ids !== null && ! in_array($companyId, $ids, true)) {
            throw new ApiException('Content must belong to your company.', 422);
        }

        if ($ids === null) {
            return $companyId;
        }

        if ($companyId !== null) {
            return $companyId;
        }

        $companyId = $this->primaryCompanyId($actor) ?? ($ids[0] ?? null);

        if ($companyId === null) {
            throw new ApiException('Content must belong to a company.', 422);
        }

        return $companyId;
    }

    public function primaryCompanyId(User $user): ?int
    {
        $id = DB::table('company_user')
            ->where('user_id', $user->id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->value('company_id');

        return $id === null ? null : (int) $id;
    }

    private function requestedCompanyId(mixed $requested): ?int
    {
        if ($requested === null || $requested === '') {
            return null;
        }

        $query = DB::table('companies')->whereNull('deleted_at');
        if (is_numeric($requested)) {
            $id = $query->where('id', (int) $requested)->value('id');
        } else {
            $id = $query->where('uuid', (string) $requested)->value('id');
        }

        if ($id === null) {
            throw new ApiException('Company not found.', 404);
        }

        return (int) $id;
    }
}
