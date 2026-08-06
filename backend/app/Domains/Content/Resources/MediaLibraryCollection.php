<?php

namespace App\Domains\Content\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MediaLibraryCollection extends ResourceCollection
{
    public $collects = MediaLibraryResource::class;

    public function toArray($request): array
    {
        return [
            'items' => $this->collection,
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
    }
}
