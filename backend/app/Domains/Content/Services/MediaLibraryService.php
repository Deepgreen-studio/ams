<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Enums\MediaType;
use App\Domains\Content\Events\MediaLibraryDeleted;
use App\Domains\Content\Events\MediaLibraryReplaced;
use App\Domains\Content\Events\MediaLibraryUploaded;
use App\Domains\Content\Models\MediaLibraryItem;
use App\Domains\Content\Repositories\MediaFolderRepository;
use App\Domains\Content\Repositories\MediaLibraryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaLibraryService
{
    public function __construct(
        private readonly MediaLibraryRepository $mediaLibraryRepository,
        private readonly MediaFolderRepository $folderRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['folder']) && $filters['folder'] !== 'root') {
            $folder = $this->folderRepository->findByIdentifierOrFail((string) $filters['folder']);
            $filters['folder_id'] = $folder->id;
        }

        return $this->mediaLibraryRepository->paginateFiltered($filters);
    }

    public function show(string $identifier): MediaLibraryItem
    {
        return $this->mediaLibraryRepository->findByIdentifierOrFail($identifier)
            ->load(['folder:id,uuid,name', 'uploader:id,uuid,full_name,email', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
    }

    /**
     * @param  list<UploadedFile>|UploadedFile  $files
     * @param  array<string, mixed>  $options
     * @return list<MediaLibraryItem>
     */
    public function upload(UploadedFile|array $files, ?string $folderIdentifier, User $actor, array $options = []): array
    {
        $uploaded = is_array($files) ? $files : [$files];
        $folderId = null;
        $disk = $this->disk();

        if ($folderIdentifier && $folderIdentifier !== 'root') {
            $folderId = $this->folderRepository->findByIdentifierOrFail($folderIdentifier)->id;
        }

        $results = [];

        foreach ($uploaded as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $this->assertValidUpload($file);

            $results[] = DB::transaction(function () use ($file, $folderId, $disk, $actor, $options): MediaLibraryItem {
                $stored = $this->storeFile($file, $folderId, $disk);
                $extension = $stored['extension'];
                $type = MediaType::fromExtension($extension);
                $displayName = (string) ($options['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: $stored['filename']);

                $item = $this->mediaLibraryRepository->createItem([
                    'media_group_uuid' => (string) Str::uuid(),
                    'folder_id' => $folderId,
                    'version' => 1,
                    'is_current' => true,
                    'name' => $displayName,
                    'original_name' => $file->getClientOriginalName(),
                    'filename' => $stored['filename'],
                    'extension' => $extension,
                    'mime_type' => (string) ($file->getClientMimeType() ?: 'application/octet-stream'),
                    'type' => $type->value,
                    'size' => (int) ($file->getSize() ?: 0),
                    'disk' => $disk,
                    'path' => $stored['path'],
                    'url' => Storage::disk($disk)->url($stored['path']),
                    'alt_text' => $options['alt_text'] ?? null,
                    'caption' => $options['caption'] ?? null,
                    'description' => $options['description'] ?? null,
                    'metadata' => $this->buildMetadata($file, $options),
                    'checksum' => $stored['checksum'],
                    'uploaded_by' => $actor->id,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                event(new MediaLibraryUploaded($item, $actor));

                return $item;
            });
        }

        if ($results === []) {
            throw new ApiException('No valid files uploaded.', 422);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): MediaLibraryItem
    {
        return DB::transaction(function () use ($identifier, $data, $actor): MediaLibraryItem {
            $item = $this->mediaLibraryRepository->findByIdentifierOrFail($identifier);
            $payload = ['updated_by' => $actor->id];

            foreach (['name', 'alt_text', 'caption', 'description'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if (array_key_exists('folder_id', $data) || array_key_exists('folder', $data)) {
                $folderRef = $data['folder_id'] ?? $data['folder'] ?? null;
                if ($folderRef === null || $folderRef === '' || $folderRef === 'root') {
                    $payload['folder_id'] = null;
                } else {
                    $payload['folder_id'] = $this->folderRepository->findByIdentifierOrFail((string) $folderRef)->id;
                }
            }

            if (array_key_exists('metadata', $data) && is_array($data['metadata'])) {
                $payload['metadata'] = array_merge(is_array($item->metadata) ? $item->metadata : [], $data['metadata']);
            }

            return $this->mediaLibraryRepository->updateItem($item, $payload);
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function replace(string $identifier, UploadedFile $file, User $actor, array $options = []): MediaLibraryItem
    {
        $this->assertValidUpload($file);

        return DB::transaction(function () use ($identifier, $file, $actor, $options): MediaLibraryItem {
            $current = $this->mediaLibraryRepository->findByIdentifierOrFail($identifier);
            if (! $current->is_current) {
                $current = $this->mediaLibraryRepository->findCurrentByGroup($current->media_group_uuid)
                    ?? $current;
            }

            $disk = $this->disk();
            $stored = $this->storeFile($file, $current->folder_id, $disk);
            $extension = $stored['extension'];
            $type = MediaType::fromExtension($extension);

            $this->mediaLibraryRepository->markGroupNotCurrent($current->media_group_uuid);

            $item = $this->mediaLibraryRepository->createItem([
                'media_group_uuid' => $current->media_group_uuid,
                'folder_id' => $current->folder_id,
                'version' => $this->mediaLibraryRepository->nextVersionNumber($current->media_group_uuid),
                'is_current' => true,
                'name' => (string) ($options['name'] ?? $current->name),
                'original_name' => $file->getClientOriginalName(),
                'filename' => $stored['filename'],
                'extension' => $extension,
                'mime_type' => (string) ($file->getClientMimeType() ?: 'application/octet-stream'),
                'type' => $type->value,
                'size' => (int) ($file->getSize() ?: 0),
                'disk' => $disk,
                'path' => $stored['path'],
                'url' => Storage::disk($disk)->url($stored['path']),
                'alt_text' => $options['alt_text'] ?? $current->alt_text,
                'caption' => $options['caption'] ?? $current->caption,
                'description' => $options['description'] ?? $current->description,
                'metadata' => array_merge(
                    is_array($current->metadata) ? $current->metadata : [],
                    $this->buildMetadata($file, $options),
                    ['replaced_from_version' => $current->version]
                ),
                'checksum' => $stored['checksum'],
                'uploaded_by' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new MediaLibraryReplaced($item, $actor, $current));

            return $item;
        });
    }

    /**
     * @return Collection<int, MediaLibraryItem>
     */
    public function versions(string $identifier): Collection
    {
        $item = $this->mediaLibraryRepository->findByIdentifierOrFail($identifier);

        return $this->mediaLibraryRepository->versionsForGroup($item->media_group_uuid);
    }

    public function restoreVersion(string $identifier, string $versionIdentifier, User $actor): MediaLibraryItem
    {
        return DB::transaction(function () use ($identifier, $versionIdentifier, $actor): MediaLibraryItem {
            $current = $this->mediaLibraryRepository->findByIdentifierOrFail($identifier);
            $history = $this->mediaLibraryRepository->findByIdentifierOrFail($versionIdentifier);

            if ($history->media_group_uuid !== $current->media_group_uuid) {
                throw new ApiException('Version does not belong to this media item.', 422);
            }

            if (! Storage::disk($history->disk)->exists($history->path)) {
                throw new ApiException('Historical file is missing from storage.', 422);
            }

            $disk = $this->disk();
            $extension = $history->extension;
            $filename = sprintf('%s.%s', Str::uuid()->toString(), $extension);
            $directory = $this->directoryFor($current->folder_id);
            $newPath = $directory.'/'.$filename;

            Storage::disk($disk)->put($newPath, Storage::disk($history->disk)->get($history->path));

            $this->mediaLibraryRepository->markGroupNotCurrent($current->media_group_uuid);

            $item = $this->mediaLibraryRepository->createItem([
                'media_group_uuid' => $current->media_group_uuid,
                'folder_id' => $current->folder_id,
                'version' => $this->mediaLibraryRepository->nextVersionNumber($current->media_group_uuid),
                'is_current' => true,
                'name' => $history->name,
                'original_name' => $history->original_name,
                'filename' => $filename,
                'extension' => $extension,
                'mime_type' => $history->mime_type,
                'type' => $history->type instanceof MediaType ? $history->type->value : (string) $history->type,
                'size' => $history->size,
                'disk' => $disk,
                'path' => $newPath,
                'url' => Storage::disk($disk)->url($newPath),
                'alt_text' => $history->alt_text,
                'caption' => $history->caption,
                'description' => $history->description,
                'metadata' => array_merge(is_array($history->metadata) ? $history->metadata : [], [
                    'restored_from_version' => $history->version,
                ]),
                'checksum' => $history->checksum,
                'uploaded_by' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new MediaLibraryReplaced($item, $actor, $current));

            return $item;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $item = $this->mediaLibraryRepository->findByIdentifierOrFail($identifier);
            $this->mediaLibraryRepository->updateItem($item, ['updated_by' => $actor->id]);
            $item->delete();
            event(new MediaLibraryDeleted($item, $actor));
        });
    }

    public function restore(string $identifier, User $actor): MediaLibraryItem
    {
        return DB::transaction(function () use ($identifier, $actor): MediaLibraryItem {
            $item = $this->mediaLibraryRepository->findByIdentifierOrFail($identifier, withTrashed: true);
            if (! $item->trashed()) {
                throw new ApiException('Media item is not deleted.', 422);
            }
            $item->restore();

            return $this->mediaLibraryRepository->updateItem($item, ['updated_by' => $actor->id]);
        });
    }

    public function download(string $identifier): StreamedResponse
    {
        $item = $this->mediaLibraryRepository->findByIdentifierOrFail($identifier);

        if (! Storage::disk($item->disk)->exists($item->path)) {
            throw new ApiException('Media file is missing from storage.', 404);
        }

        return Storage::disk($item->disk)->download($item->path, $item->original_name);
    }

    /**
     * Compatibility helper for editor image uploads.
     *
     * @return array<string, mixed>
     */
    public function uploadForEditor(UploadedFile $file, User $actor): array
    {
        $items = $this->upload($file, null, $actor, ['source' => 'content_editor']);
        $media = $items[0];

        return [
            'uuid' => $media->uuid,
            'filename' => $media->filename,
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'path' => $media->path,
            'url' => $media->public_url,
            'type' => $media->type instanceof MediaType ? $media->type->value : $media->type,
            'uploaded_by' => $actor->id,
        ];
    }

    protected function disk(): string
    {
        return (string) config('filesystems.media_library_disk', 'public') ?: 'public';
    }

    protected function directoryFor(?int $folderId): string
    {
        $base = $folderId ? "cms-media/folders/{$folderId}" : 'cms-media/root';

        return $base.'/'.now()->format('Y/m');
    }

    /**
     * @return array{filename: string, extension: string, path: string, checksum: string|null}
     */
    protected function storeFile(UploadedFile $file, ?int $folderId, string $disk): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = sprintf('%s.%s', Str::uuid()->toString(), $extension);
        $directory = $this->directoryFor($folderId);
        $path = $file->storeAs($directory, $filename, $disk);

        if (! $path) {
            throw new ApiException('Unable to store media file.', 500);
        }

        $checksum = null;
        try {
            $checksum = hash_file('sha256', $file->getRealPath() ?: '') ?: null;
        } catch (\Throwable) {
            $checksum = null;
        }

        return [
            'filename' => $filename,
            'extension' => $extension,
            'path' => $path,
            'checksum' => $checksum,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function buildMetadata(UploadedFile $file, array $options = []): array
    {
        $metadata = [
            'source' => $options['source'] ?? 'media_library',
            'client_mime' => $file->getClientMimeType(),
        ];

        if (! empty($options['crop']) && is_array($options['crop'])) {
            $metadata['crop'] = $options['crop'];
        }

        if (! empty($options['width'])) {
            $metadata['width'] = (int) $options['width'];
        }
        if (! empty($options['height'])) {
            $metadata['height'] = (int) $options['height'];
        }

        return $metadata;
    }

    protected function assertValidUpload(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $allowed = MediaType::allowedExtensions();

        if (! in_array($extension, $allowed, true)) {
            throw new ApiException('Unsupported file type. Allowed: '.implode(', ', $allowed), 422);
        }

        $type = MediaType::fromExtension($extension);
        $sizeKb = (int) ceil(($file->getSize() ?: 0) / 1024);
        $maxKb = match ($type) {
            MediaType::Image => 10240,
            MediaType::Video => 102400,
            MediaType::Document => 25600,
            MediaType::Archive => 51200,
            MediaType::Other => 10240,
        };

        if ($sizeKb > $maxKb) {
            throw new ApiException("File exceeds maximum size of {$maxKb} KB for type {$type->value}.", 422);
        }
    }
}
