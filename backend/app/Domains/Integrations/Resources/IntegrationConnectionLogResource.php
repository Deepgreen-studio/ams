<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationConnectionLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'integration_id' => $this->integration_id,
            'company_id' => $this->company_id,
            'request_type' => $this->request_type?->value ?? $this->request_type,
            'method' => $this->method,
            'url' => $this->url,
            'request_headers' => $this->request_headers,
            'request_query' => $this->request_query,
            'request_body' => $this->request_body,
            'response_status' => $this->response_status,
            'response_headers' => $this->response_headers,
            'response_body' => $this->response_body,
            'duration_ms' => $this->duration_ms,
            'attempts' => $this->attempts,
            'success' => $this->success,
            'error_message' => $this->error_message,
            'actor' => $this->whenLoaded('actor', function () {
                return $this->actor ? [
                    'id' => $this->actor->id,
                    'uuid' => $this->actor->uuid,
                    'full_name' => $this->actor->full_name,
                    'email' => $this->actor->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
