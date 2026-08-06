<?php

namespace App\Domains\Settings\Services;

use App\Domains\Settings\Events\FolderCreated;
use App\Domains\Settings\Events\FolderDeleted;
use App\Domains\Settings\Models\FileFolder;
use App\Domains\Settings\Repositories\FolderRepository;
use App\Domains\Settings\Repositories\MediaRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FileManagerService
{
    public function __construct(
        private readonly FolderRepository $folderRepository,
        private readonly MediaRepository $mediaRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, FileFolder>
     */
    public function list(array $filters = []): Collection
    {
        return $this->folderRepository->filtered($filters);
    }

    /**
     * @return Collection<int, FileFolder>
     */
    public function tree(): Collection
    {
        return $this->folderRepository->tree();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): FileFolder
    {
        return DB::transaction(function () use ($data, $actor): FileFolder {
            $parentId = null;
            if (! empty($data['parent_id'])) {
                $parentId = $this->folderRepository->findByIdentifierOrFail((string) $data['parent_id'])->id;
            }

            /** @var FileFolder $folder */
            $folder = $this->folderRepository->create([
                'parent_id' => $parentId,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
                'created_by' => $actor->id,
            ]);

            event(new FolderCreated($folder, $actor));

            return $folder->load('parent');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): FileFolder
    {
        return DB::transaction(function () use ($identifier, $data, $actor): FileFolder {
            $folder = $this->folderRepository->findByIdentifierOrFail($identifier);

            if (array_key_exists('name', $data) && filled($data['name'])) {
                $folder->name = $data['name'];
                $folder->slug = Str::slug((string) $data['name']).'-'.Str::lower(Str::random(4));
            }

            if (array_key_exists('parent_id', $data)) {
                if (blank($data['parent_id'])) {
                    $folder->parent_id = null;
                } else {
                    $parent = $this->folderRepository->findByIdentifierOrFail((string) $data['parent_id']);
                    if ($parent->id === $folder->id) {
                        throw new ApiException('A folder cannot be its own parent.', 422);
                    }
                    $folder->parent_id = $parent->id;
                }
            }

            $folder->save();

            return $folder->refresh()->load('parent');
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $folder = $this->folderRepository->findByIdentifierOrFail($identifier);

            if ($folder->children()->exists()) {
                throw new ApiException('Remove nested folders before deleting this folder.', 422);
            }

            if ($folder->media()->exists()) {
                throw new ApiException('Move or delete media files before deleting this folder.', 422);
            }

            $folder->delete();
            event(new FolderDeleted($folder, $actor));
        });
    }
}
