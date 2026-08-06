<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsDashboardShareType;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Models\AnalyticsDashboardShare;
use App\Domains\Analytics\Repositories\AnalyticsDashboardShareRepository;
use App\Domains\Companies\Models\Company;
use App\Domains\Roles\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardShareService
{
    public function __construct(
        private readonly AnalyticsDashboardShareRepository $shareRepository,
    ) {}

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AnalyticsDashboardShare>
     */
    public function list(AnalyticsDashboard $dashboard): \Illuminate\Database\Eloquent\Collection
    {
        return $this->shareRepository->forDashboard($dashboard->id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function share(AnalyticsDashboard $dashboard, array $data, User $actor): AnalyticsDashboardShare
    {
        return DB::transaction(function () use ($dashboard, $data, $actor): AnalyticsDashboardShare {
            $shareType = (string) $data['share_type'];
            $shareId = $this->resolveShareId($shareType, $data);

            $existing = $this->shareRepository->findExisting($dashboard->id, $shareType, $shareId);

            if ($existing) {
                $existing->update([
                    'can_edit' => (bool) ($data['can_edit'] ?? $existing->can_edit),
                    'shared_by' => $actor->id,
                ]);

                $dashboard->update([
                    'is_shared' => true,
                    'visibility' => $dashboard->visibility === AnalyticsDashboardVisibility::Personal
                        ? AnalyticsDashboardVisibility::Shared->value
                        : ($dashboard->visibility?->value ?? $dashboard->visibility),
                    'updated_by' => $actor->id,
                ]);

                return $existing->refresh()->load('sharer:id,uuid,full_name,email');
            }

            /** @var AnalyticsDashboardShare $share */
            $share = $this->shareRepository->create([
                'analytics_dashboard_id' => $dashboard->id,
                'share_type' => $shareType,
                'share_id' => $shareId,
                'can_edit' => (bool) ($data['can_edit'] ?? false),
                'shared_by' => $actor->id,
            ]);

            $visibility = match ($shareType) {
                AnalyticsDashboardShareType::Role->value => AnalyticsDashboardVisibility::Role->value,
                AnalyticsDashboardShareType::Company->value => AnalyticsDashboardVisibility::Company->value,
                default => AnalyticsDashboardVisibility::Shared->value,
            };

            $dashboard->update([
                'is_shared' => true,
                'visibility' => $visibility,
                'updated_by' => $actor->id,
            ]);

            return $share->load('sharer:id,uuid,full_name,email');
        });
    }

    public function revoke(AnalyticsDashboard $dashboard, AnalyticsDashboardShare $share, User $actor): void
    {
        DB::transaction(function () use ($dashboard, $share, $actor): void {
            if ($share->analytics_dashboard_id !== $dashboard->id) {
                abort(404, 'Share not found on this dashboard.');
            }

            $share->delete();

            $remaining = $this->shareRepository->forDashboard($dashboard->id)->count();

            $dashboard->update([
                'is_shared' => $remaining > 0,
                'visibility' => $remaining > 0
                    ? ($dashboard->visibility?->value ?? AnalyticsDashboardVisibility::Shared->value)
                    : AnalyticsDashboardVisibility::Personal->value,
                'updated_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  Collection<int, AnalyticsDashboardShare>  $shares
     * @return list<array<string, mixed>>
     */
    public function enrichShares(Collection $shares): array
    {
        return $shares->map(function (AnalyticsDashboardShare $share): array {
            $target = $this->resolveTargetLabel($share);

            return [
                'id' => $share->id,
                'uuid' => $share->uuid,
                'share_type' => $share->share_type?->value ?? $share->share_type,
                'share_id' => $share->share_id,
                'can_edit' => (bool) $share->can_edit,
                'target' => $target,
                'shared_by' => $share->sharer ? [
                    'id' => $share->sharer->id,
                    'uuid' => $share->sharer->uuid,
                    'full_name' => $share->sharer->full_name,
                    'email' => $share->sharer->email,
                ] : null,
                'created_at' => $share->created_at,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveShareId(string $shareType, array $data): int
    {
        if (! empty($data['share_id']) && is_numeric($data['share_id'])) {
            $id = (int) $data['share_id'];
            $this->assertTargetExists($shareType, $id);

            return $id;
        }

        $identifier = (string) ($data['share_uuid'] ?? $data['identifier'] ?? '');

        return match ($shareType) {
            AnalyticsDashboardShareType::User->value => tap(
                User::query()->where('uuid', $identifier)->value('id'),
                fn ($id) => $id ?: abort(422, 'User not found.')
            ),
            AnalyticsDashboardShareType::Role->value => tap(
                Role::query()->where('uuid', $identifier)->orWhere('name', $identifier)->value('id'),
                fn ($id) => $id ?: abort(422, 'Role not found.')
            ),
            AnalyticsDashboardShareType::Company->value => tap(
                Company::query()->where('uuid', $identifier)->value('id'),
                fn ($id) => $id ?: abort(422, 'Company not found.')
            ),
            default => abort(422, 'Invalid share type.'),
        };
    }

    protected function assertTargetExists(string $shareType, int $id): void
    {
        $exists = match ($shareType) {
            AnalyticsDashboardShareType::User->value => User::query()->whereKey($id)->exists(),
            AnalyticsDashboardShareType::Role->value => Role::query()->whereKey($id)->exists(),
            AnalyticsDashboardShareType::Company->value => Company::query()->whereKey($id)->exists(),
            default => false,
        };

        if (! $exists) {
            abort(422, 'Share target not found.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function resolveTargetLabel(AnalyticsDashboardShare $share): ?array
    {
        $type = $share->share_type instanceof AnalyticsDashboardShareType
            ? $share->share_type->value
            : (string) $share->share_type;

        return match ($type) {
            AnalyticsDashboardShareType::User->value => User::query()
                ->whereKey($share->share_id)
                ->first(['id', 'uuid', 'full_name', 'email'])
                ?->only(['id', 'uuid', 'full_name', 'email']),
            AnalyticsDashboardShareType::Role->value => Role::query()
                ->whereKey($share->share_id)
                ->first(['id', 'uuid', 'name', 'display_name'])
                ?->only(['id', 'uuid', 'name', 'display_name']),
            AnalyticsDashboardShareType::Company->value => Company::query()
                ->whereKey($share->share_id)
                ->first(['id', 'uuid', 'company_name'])
                ?->only(['id', 'uuid', 'company_name']),
            default => null,
        };
    }
}
