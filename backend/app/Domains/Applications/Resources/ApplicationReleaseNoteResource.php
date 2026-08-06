<?php

namespace App\Domains\Applications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationReleaseNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'release_id' => $this->release_id,
            'locale' => $this->locale,
            'title' => $this->title,
            'content' => $this->content,
            'audience' => $this->audience?->value ?? $this->audience,
            'audience_label' => $this->audience?->label(),
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
