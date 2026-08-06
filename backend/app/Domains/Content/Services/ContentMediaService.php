<?php

namespace App\Domains\Content\Services;

use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentMediaService
{
    /**
     * @return array{uuid: string, filename: string, original_name: string, mime_type: string, size: int, path: string, url: string}
     */
    public function uploadImage(UploadedFile $file, User $actor): array
    {
        $this->assertValidImage($file);

        $disk = (string) config('filesystems.default', 'public');
        if ($disk === 'local') {
            $disk = 'public';
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = sprintf('%s.%s', Str::uuid()->toString(), $extension);
        $directory = 'uploads/content/'.now()->format('Y/m');
        $path = $file->storeAs($directory, $filename, $disk);

        if (! $path) {
            throw new ApiException('Unable to store content media file.', 500);
        }

        return [
            'uuid' => (string) Str::uuid(),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getClientMimeType(),
            'size' => (int) ($file->getSize() ?: 0),
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'uploaded_by' => $actor->id,
        ];
    }

    protected function assertValidImage(UploadedFile $file): void
    {
        $maxKb = 10240;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $sizeKb = (int) ceil(($file->getSize() ?: 0) / 1024);

        if (! in_array($extension, $allowed, true)) {
            throw new ApiException('Only image files are allowed (jpg, jpeg, png, gif, webp, svg).', 422);
        }

        if ($sizeKb > $maxKb) {
            throw new ApiException("Image exceeds maximum size of {$maxKb} KB.", 422);
        }
    }
}
