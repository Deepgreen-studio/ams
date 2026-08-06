<?php

namespace App\Domains\Notifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @deprecated Use NotificationResource. Kept for backward-compatible response shapes.
 */
class InAppNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (new NotificationResource($this->resource))->toArray($request);
    }
}
