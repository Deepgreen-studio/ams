<?php

namespace App\Domains\Content\Resources;

use App\Domains\Content\Enums\MediaType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaLibraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type instanceof MediaType ? $this->type->value : $this->type;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'media_group_uuid' => $this->media_group_uuid,
            'folder_id' => $this->folder_id,
            'folder' => $this->whenLoaded('folder', function () {
                return $this->folder ? [
                    'id' => $this->folder->id,
                    'uuid' => $this->folder->uuid,
                    'name' => $this->folder->name,
                ] : null;
            }),
            'version' => (int) $this->version,
            'is_current' => (bool) $this->is_current,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'filename' => $this->filename,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'type' => $type,
            'size' => (int) $this->size,
            'human_size' => $this->human_size,
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $this->public_url,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'checksum' => $this->checksum,
            'is_image' => (bool) $this->is_image,
            'is_previewable' => (bool) $this->is_previewable,
            'uploader' => $this->whenLoaded('uploader', function () {
                return $this->uploader ? [
                    'id' => $this->uploader->id,
                    'uuid' => $this->uploader->uuid,
                    'full_name' => $this->uploader->full_name,
                    'email' => $this->uploader->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
