<?php

namespace App\Domains\Applications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'application' => $this->whenLoaded('application', function () {
                return [
                    'id' => $this->application->id,
                    'uuid' => $this->application->uuid,
                    'name' => $this->application->name,
                    'slug' => $this->application->slug,
                    'current_version' => $this->application->current_version,
                    'minimum_supported_version' => $this->application->minimum_supported_version,
                ];
            }),
            'version_number' => $this->version_number,
            'major' => $this->major,
            'minor' => $this->minor,
            'patch' => $this->patch,
            'build_number' => $this->build_number,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'release_date' => $this->release_date,
            'minimum_supported_version' => $this->minimum_supported_version,
            'release_notes' => $this->release_notes,
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
}
