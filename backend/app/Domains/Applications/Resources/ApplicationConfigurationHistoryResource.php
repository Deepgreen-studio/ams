<?php

namespace App\Domains\Applications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationConfigurationHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'configuration_id' => $this->configuration_id,
            'version' => $this->version,
            'status' => $this->status,
            'change_summary' => $this->change_summary,
            'payload' => $this->maskedPayload(is_array($this->payload) ? $this->payload : []),
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function maskedPayload(array $payload): array
    {
        foreach (['api_key', 'api_secret'] as $key) {
            if (! empty($payload[$key])) {
                $payload[$key] = '********';
            }
        }

        return $payload;
    }
}
