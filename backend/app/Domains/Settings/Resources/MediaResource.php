<?php

namespace App\Domains\Settings\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'folder_id' => $this->folder_id,
            'folder' => $this->whenLoaded('folder', fn () => [
                'uuid' => $this->folder?->uuid,
                'name' => $this->folder?->name,
            ]),
            'filename' => $this->filename,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $this->public_url,
            'meta' => $this->meta,
            'uploaded_by' => $this->whenLoaded('uploader', fn () => [
                'uuid' => $this->uploader?->uuid,
                'full_name' => $this->uploader?->full_name,
                'email' => $this->uploader?->email,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
