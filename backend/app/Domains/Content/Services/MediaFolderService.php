<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Models\MediaFolder;
use App\Domains\Content\Repositories\MediaFolderRepository;
use App\Domains\Content\Repositories\MediaLibraryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MediaFolderService
{
    public function __construct(
        private readonly MediaFolderRepository $folderRepository,
        private readonly MediaLibraryRepository $mediaLibraryRepository
    ) {}

    /**
     * @return Collection<int, MediaFolder>
     */
    public function list(): Collection
    {
        return $this->folderRepository->listAll();
    }

    /**
     * @return Collection<int, MediaFolder>
     */
    public function tree(): Collection
    {
        return $this->folderRepository->tree(activeOnly: false);
    }

    public function show(string $identifier): MediaFolder
    {
        return $this->folderRepository->findByIdentifierOrFail($identifier)
            ->load(['parent:id,uuid,name', 'children', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): MediaFolder
    {
        return DB::transaction(function () use ($data, $actor): MediaFolder {
            $parentId = null;
            if (! empty($data['parent_id'])) {
                $parent = $this->folderRepository->findByIdentifierOrFail((string) $data['parent_id']);
                $parentId = $parent->id;
            }

            return $this->folderRepository->createFolder([
                'parent_id' => $parentId,
                'name' => (string) $data['name'],
                'slug' => $this->folderRepository->uniqueSlug($parentId, (string) $data['name']),
                'description' => $data['description'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): MediaFolder
    {
        return DB::transaction(function () use ($identifier, $data, $actor): MediaFolder {
            $folder = $this->folderRepository->findByIdentifierOrFail($identifier);
            $payload = ['updated_by' => $actor->id];

            if (array_key_exists('name', $data) && filled($data['name'])) {
                $payload['name'] = (string) $data['name'];
                $parentId = $folder->parent_id;
                if (array_key_exists('parent_id', $data)) {
                    $parentId = $this->resolveParentId($data['parent_id'], $folder);
                    $payload['parent_id'] = $parentId;
                }
                $payload['slug'] = $this->folderRepository->uniqueSlug($parentId, (string) $data['name'], $folder->id);
            } elseif (array_key_exists('parent_id', $data)) {
                $payload['parent_id'] = $this->resolveParentId($data['parent_id'], $folder);
            }

            if (array_key_exists('description', $data)) {
                $payload['description'] = $data['description'];
            }
            if (array_key_exists('sort_order', $data)) {
                $payload['sort_order'] = (int) $data['sort_order'];
            }
            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = (bool) $data['is_active'];
            }

            return $this->folderRepository->updateFolder($folder, $payload);
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $folder = $this->folderRepository->findByIdentifierOrFail($identifier);

            $hasChildren = $folder->children()->exists();
            $hasMedia = $this->mediaLibraryRepository->paginateFiltered([
                'folder_id' => $folder->id,
                'per_page' => 1,
            ])->total() > 0;

            if ($hasChildren || $hasMedia) {
                throw new ApiException('Folder must be empty before deletion.', 422);
            }

            $this->folderRepository->updateFolder($folder, ['updated_by' => $actor->id]);
            $folder->delete();
        });
    }

    public function restore(string $identifier, User $actor): MediaFolder
    {
        return DB::transaction(function () use ($identifier, $actor): MediaFolder {
            $folder = $this->folderRepository->findByIdentifierOrFail($identifier, withTrashed: true);
            if (! $folder->trashed()) {
                throw new ApiException('Folder is not deleted.', 422);
            }
            $folder->restore();

            return $this->folderRepository->updateFolder($folder, ['updated_by' => $actor->id]);
        });
    }

    protected function resolveParentId(mixed $parentIdentifier, MediaFolder $folder): ?int
    {
        if ($parentIdentifier === null || $parentIdentifier === '' || $parentIdentifier === 'root') {
            return null;
        }

        $parent = $this->folderRepository->findByIdentifierOrFail((string) $parentIdentifier);
        if ($parent->id === $folder->id) {
            throw new ApiException('A folder cannot be its own parent.', 422);
        }

        $cursor = $parent;
        while ($cursor->parent_id) {
            if ((int) $cursor->parent_id === (int) $folder->id) {
                throw new ApiException('Cannot move a folder under its descendant.', 422);
            }
            /** @var MediaFolder|null $next */
            $next = MediaFolder::query()->find($cursor->parent_id);
            if (! $next) {
                break;
            }
            $cursor = $next;
        }

        return $parent->id;
    }
}
