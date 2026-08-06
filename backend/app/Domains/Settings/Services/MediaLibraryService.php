<?php

namespace App\Domains\Settings\Services;

use App\Domains\Settings\Events\MediaDeleted;
use App\Domains\Settings\Events\MediaUploaded;
use App\Domains\Settings\Models\MediaFile;
use App\Domains\Settings\Repositories\FolderRepository;
use App\Domains\Settings\Repositories\MediaRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLibraryService
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly FolderRepository $folderRepository,
        private readonly SystemSettingService $settingService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['folder'])) {
            $folder = $this->folderRepository->findByIdentifierOrFail((string) $filters['folder']);
            $filters['folder_id'] = $folder->id;
        }

        return $this->mediaRepository->paginateFiltered($filters);
    }

    public function show(string $identifier): MediaFile
    {
        return $this->mediaRepository->findByIdentifierOrFail($identifier)
            ->load(['folder', 'uploader:id,uuid,full_name,email']);
    }

    /**
     * @param  list<UploadedFile>|UploadedFile  $files
     * @return list<MediaFile>
     */
    public function upload(UploadedFile|array $files, ?string $folderIdentifier, User $actor): array
    {
        $uploaded = is_array($files) ? $files : [$files];
        $folderId = null;

        if ($folderIdentifier) {
            $folderId = $this->folderRepository->findByIdentifierOrFail($folderIdentifier)->id;
        }

        $disk = (string) $this->settingService->getValue('storage', 'default_disk', config('filesystems.media_library_disk', 'public'));
        $maxKb = (int) $this->settingService->getValue('storage', 'max_upload_kb', 10240);
        $allowed = $this->settingService->getValue('storage', 'allowed_extensions', ['jpg', 'jpeg', 'png', 'svg', 'pdf', 'docx', 'xlsx', 'zip']);
        if (! is_array($allowed)) {
            $allowed = ['jpg', 'jpeg', 'png', 'svg', 'pdf', 'docx', 'xlsx', 'zip'];
        }
        $allowed = array_map(strtolower(...), $allowed);

        $results = [];

        foreach ($uploaded as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $this->assertValidUpload($file, $maxKb, $allowed);

            $results[] = DB::transaction(function () use ($file, $folderId, $disk, $actor): MediaFile {
                $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                $filename = sprintf('%s.%s', Str::uuid()->toString(), $extension);
                $directory = $folderId ? "media/folders/{$folderId}" : 'media/root';
                $path = $file->storeAs($directory, $filename, $disk);

                if (! $path) {
                    throw new ApiException('Unable to store media file.', 500);
                }

                /** @var MediaFile $media */
                $media = $this->mediaRepository->create([
                    'folder_id' => $folderId,
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $extension,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize() ?: 0,
                    'disk' => $disk,
                    'path' => $path,
                    'url' => Storage::disk($disk)->url($path),
                    'uploaded_by' => $actor->id,
                ]);

                event(new MediaUploaded($media, $actor));

                return $media->load(['folder', 'uploader:id,uuid,full_name,email']);
            });
        }

        if ($results === []) {
            throw new ApiException('No valid files uploaded.', 422);
        }

        return $results;
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $media = $this->mediaRepository->findByIdentifierOrFail($identifier);

            if (filled($media->path) && Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }

            $media->delete();
            event(new MediaDeleted($media, $actor));
        });
    }

    public function move(string $identifier, ?string $folderIdentifier, User $actor): MediaFile
    {
        return DB::transaction(function () use ($identifier, $folderIdentifier, $actor): MediaFile {
            $media = $this->mediaRepository->findByIdentifierOrFail($identifier);
            $folderId = null;

            if ($folderIdentifier) {
                $folderId = $this->folderRepository->findByIdentifierOrFail($folderIdentifier)->id;
            }

            $media->folder_id = $folderId;
            $media->save();

            return $media->refresh()->load(['folder', 'uploader:id,uuid,full_name,email']);
        });
    }

    /**
     * @param  list<string>  $allowed
     */
    protected function assertValidUpload(UploadedFile $file, int $maxKb, array $allowed): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        if ($extension === '' || ! in_array($extension, $allowed, true)) {
            throw new ApiException("File type .{$extension} is not allowed.", 422);
        }

        $sizeKb = (int) ceil(($file->getSize() ?: 0) / 1024);
        if ($sizeKb > $maxKb) {
            throw new ApiException("File exceeds maximum upload size of {$maxKb} KB.", 422);
        }
    }
}
