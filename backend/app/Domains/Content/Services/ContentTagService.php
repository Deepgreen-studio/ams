<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Models\ContentTag;
use App\Domains\Content\Repositories\ContentTagRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentTagService
{
    public function __construct(
        private readonly ContentTagRepository $tagRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->tagRepository->paginateFiltered($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): ContentTag
    {
        return $this->tagRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): ContentTag
    {
        return $this->find($identifier)
            ->load(['creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email'])
            ->loadCount('contents');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ContentTag
    {
        return DB::transaction(function () use ($data, $actor): ContentTag {
            $name = (string) $data['name'];

            $tag = $this->tagRepository->createTag([
                'name' => $name,
                'slug' => $this->resolveUniqueSlug($data['slug'] ?? null, $name),
                'description' => $data['description'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            activity('content')
                ->causedBy($actor)
                ->performedOn($tag)
                ->withProperties(['event' => 'content_tag_created', 'name' => $tag->name])
                ->log('Content tag created');

            return $tag;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): ContentTag
    {
        return DB::transaction(function () use ($identifier, $data, $actor): ContentTag {
            $tag = $this->tagRepository->findByIdentifierOrFail($identifier);
            $payload = ['updated_by' => $actor->id];

            foreach (['name', 'description', 'seo_title', 'seo_description'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = blank($data[$field]) && $field !== 'name' ? null : $data[$field];
                }
            }

            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = (bool) $data['is_active'];
            }

            if (array_key_exists('sort_order', $data)) {
                $payload['sort_order'] = (int) $data['sort_order'];
            }

            if (array_key_exists('slug', $data) && filled($data['slug'])) {
                $payload['slug'] = $this->resolveUniqueSlug((string) $data['slug'], $tag->name, $tag->id);
            } elseif (array_key_exists('name', $payload)) {
                $payload['slug'] = $this->resolveUniqueSlug(null, (string) $payload['name'], $tag->id);
            }

            $updated = $this->tagRepository->updateTag($tag, $payload);

            activity('content')
                ->causedBy($actor)
                ->performedOn($updated)
                ->withProperties(['event' => 'content_tag_updated', 'name' => $updated->name])
                ->log('Content tag updated');

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $tag = $this->tagRepository->findByIdentifierOrFail($identifier);
            $this->tagRepository->updateTag($tag, ['updated_by' => $actor->id]);
            $tag->delete();

            activity('content')
                ->causedBy($actor)
                ->performedOn($tag)
                ->withProperties(['event' => 'content_tag_deleted', 'name' => $tag->name])
                ->log('Content tag deleted');
        });
    }

    public function restore(string $identifier, User $actor): ContentTag
    {
        return DB::transaction(function () use ($identifier, $actor): ContentTag {
            $tag = $this->tagRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $tag->trashed()) {
                throw new ApiException('Tag is not deleted.', 422);
            }

            $tag->restore();
            $restored = $this->tagRepository->updateTag($tag, ['updated_by' => $actor->id]);

            activity('content')
                ->causedBy($actor)
                ->performedOn($restored)
                ->withProperties(['event' => 'content_tag_restored', 'name' => $restored->name])
                ->log('Content tag restored');

            return $restored;
        });
    }

    /**
     * @param  array{action: string, ids: list<string>}  $payload
     * @return array{affected: int}
     */
    public function bulk(array $payload, User $actor): array
    {
        return DB::transaction(function () use ($payload, $actor): array {
            $action = (string) $payload['action'];
            $tags = $this->tagRepository->findManyByIdentifiers($payload['ids'] ?? [], withTrashed: $action === 'restore');
            $affected = 0;

            foreach ($tags as $tag) {
                match ($action) {
                    'activate' => $this->tagRepository->updateTag($tag, [
                        'is_active' => true,
                        'updated_by' => $actor->id,
                    ]),
                    'deactivate' => $this->tagRepository->updateTag($tag, [
                        'is_active' => false,
                        'updated_by' => $actor->id,
                    ]),
                    'delete' => tap($tag, function (ContentTag $item) use ($actor): void {
                        $this->tagRepository->updateTag($item, ['updated_by' => $actor->id]);
                        $item->delete();
                    }),
                    'restore' => $tag->trashed()
                        ? $this->restore($tag->uuid, $actor)
                        : null,
                    default => throw new ApiException('Unsupported bulk action.', 422),
                };
                $affected++;
            }

            activity('content')
                ->causedBy($actor)
                ->withProperties([
                    'event' => 'content_tag_bulk',
                    'action' => $action,
                    'affected' => $affected,
                    'ids' => $payload['ids'] ?? [],
                ])
                ->log('Content tag bulk '.$action);

            return ['affected' => $affected];
        });
    }

    protected function resolveUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'tag';
        $candidate = $base;
        $suffix = 2;

        while ($this->tagRepository->slugExists($candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
