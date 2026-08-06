<?php

namespace App\Domains\Support\Services;

use App\Domains\Support\Enums\CannedResponseVisibility;
use App\Domains\Support\Enums\SupportPermission;
use App\Domains\Support\Models\SupportCannedResponse;
use App\Domains\Support\Repositories\SupportCannedResponseRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SupportCannedResponseService
{
    public function __construct(
        private readonly SupportCannedResponseRepository $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $actor): array
    {
        return [
            'statistics' => $this->repository->statisticsForUser($actor),
            'recent' => SupportCannedResponse::query()
                ->with(['owner:id,uuid,full_name,email'])
                ->accessibleBy($actor)
                ->where('is_active', true)
                ->orderByDesc('usage_count')
                ->orderBy('title')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(User $actor, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($actor, $filters);
    }

    public function findAccessible(string $identifier, User $actor): SupportCannedResponse
    {
        $response = $this->repository->findByIdentifierOrFail($identifier);
        $this->assertCanView($response, $actor);

        return $response->load(['owner:id,uuid,full_name,email', 'creator:id,uuid,full_name,email']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): SupportCannedResponse
    {
        $visibility = CannedResponseVisibility::from((string) ($data['visibility'] ?? CannedResponseVisibility::Personal->value));

        if ($visibility === CannedResponseVisibility::Shared && ! $actor->can(SupportPermission::MANAGE)) {
            throw new ApiException('Only support managers can create shared canned responses.', 403);
        }

        $payload = [
            'title' => trim((string) $data['title']),
            'shortcut' => $this->normalizeShortcut($data['shortcut'] ?? null),
            'body' => (string) $data['body'],
            'body_format' => (string) ($data['body_format'] ?? 'html'),
            'visibility' => $visibility->value,
            'user_id' => $actor->id,
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ];

        return $this->repository->create($payload)->load(['owner:id,uuid,full_name,email']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): SupportCannedResponse
    {
        $response = $this->repository->findByIdentifierOrFail($identifier);
        $this->assertCanManage($response, $actor);

        $visibility = array_key_exists('visibility', $data)
            ? CannedResponseVisibility::from((string) $data['visibility'])
            : $response->visibility;

        if (
            $visibility === CannedResponseVisibility::Shared
            && ! $actor->can(SupportPermission::MANAGE)
            && ! $response->isShared()
        ) {
            throw new ApiException('Only support managers can convert responses to shared.', 403);
        }

        if (
            $visibility === CannedResponseVisibility::Personal
            && $response->isShared()
            && ! $actor->can(SupportPermission::MANAGE)
        ) {
            throw new ApiException('Only support managers can convert shared responses to personal.', 403);
        }

        $payload = [
            'updated_by' => $actor->id,
        ];

        if (array_key_exists('title', $data)) {
            $payload['title'] = trim((string) $data['title']);
        }
        if (array_key_exists('shortcut', $data)) {
            $payload['shortcut'] = $this->normalizeShortcut($data['shortcut']);
        }
        if (array_key_exists('body', $data)) {
            $payload['body'] = (string) $data['body'];
        }
        if (array_key_exists('body_format', $data)) {
            $payload['body_format'] = (string) $data['body_format'];
        }
        if (array_key_exists('visibility', $data)) {
            $payload['visibility'] = $visibility->value;
            if ($visibility === CannedResponseVisibility::Personal) {
                $payload['user_id'] = $actor->can(SupportPermission::MANAGE)
                    ? ($response->user_id ?: $actor->id)
                    : $actor->id;
            }
        }
        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }
        if (array_key_exists('sort_order', $data)) {
            $payload['sort_order'] = (int) $data['sort_order'];
        }

        return $this->repository->update($response, $payload)
            ->load(['owner:id,uuid,full_name,email']);
    }

    public function delete(string $identifier, User $actor): void
    {
        $response = $this->repository->findByIdentifierOrFail($identifier);
        $this->assertCanManage($response, $actor);
        $this->repository->delete($response);
    }

    public function markUsed(string $identifier, User $actor): SupportCannedResponse
    {
        $response = $this->findAccessible($identifier, $actor);

        if (! $response->is_active) {
            throw new ApiException('This canned response is inactive.', 422);
        }

        return $this->repository->incrementUsage($response)
            ->load(['owner:id,uuid,full_name,email']);
    }

    private function assertCanView(SupportCannedResponse $response, User $actor): void
    {
        if ($response->isShared()) {
            return;
        }

        if ($response->isOwnedBy($actor)) {
            return;
        }

        throw new ApiException('You do not have access to this canned response.', 403);
    }

    private function assertCanManage(SupportCannedResponse $response, User $actor): void
    {
        if ($actor->can(SupportPermission::MANAGE)) {
            return;
        }

        if ($response->isPersonal() && $response->isOwnedBy($actor)) {
            return;
        }

        throw new ApiException('You are not allowed to modify this canned response.', 403);
    }

    private function normalizeShortcut(mixed $shortcut): ?string
    {
        if ($shortcut === null || $shortcut === '') {
            return null;
        }

        $value = Str::of((string) $shortcut)
            ->lower()
            ->replaceMatches('/[^a-z0-9_-]+/', '-')
            ->trim('-')
            ->toString();

        return $value !== '' ? $value : null;
    }
}
