<?php

namespace App\Domains\Applications\Resources;

use App\Domains\Applications\Enums\ApplicationConfigurationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type instanceof ApplicationConfigurationType
            ? $this->type
            : ApplicationConfigurationType::tryFrom((string) $this->type);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'environment_id' => $this->environment_id,
            'environment' => $this->whenLoaded('environment', function () {
                return $this->environment ? [
                    'id' => $this->environment->id,
                    'uuid' => $this->environment->uuid,
                    'name' => $this->environment->name,
                    'slug' => $this->environment->slug,
                    'type' => $this->environment->type?->value ?? $this->environment->type,
                ] : null;
            }),
            'type' => $type?->value ?? $this->type,
            'type_label' => $type?->label(),
            'is_sensitive' => $type?->isSensitive() ?? false,
            'name' => $this->name,
            'description' => $this->description,
            'payload' => $this->maskPayload($type, is_array($this->payload) ? $this->payload : []),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'version' => $this->version,
            'is_active' => (bool) $this->is_active,
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'updater' => $this->whenLoaded('updater', function () {
                return $this->updater ? [
                    'id' => $this->updater->id,
                    'uuid' => $this->updater->uuid,
                    'full_name' => $this->updater->full_name,
                    'email' => $this->updater->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function maskPayload(?ApplicationConfigurationType $type, array $payload): array
    {
        if (! $type?->isSensitive()) {
            return $payload;
        }

        $sensitiveKeys = ['api_key', 'api_secret', 'messaging_sender_id'];
        foreach ($sensitiveKeys as $key) {
            if (! empty($payload[$key])) {
                $payload[$key] = '********';
                $payload[$key.'_configured'] = true;
            }
        }

        return $payload;
    }
}
