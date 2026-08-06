<?php

namespace App\Domains\Monitoring\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MonitoringAlertEventCollection extends ResourceCollection
{
    public $collects = MonitoringAlertEventResource::class;

    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection,
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ],
        ];
    }
}
