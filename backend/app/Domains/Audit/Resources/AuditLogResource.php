<?php

namespace App\Domains\Audit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'module' => $this->module,
            'action' => $this->action,
            'before_data' => $this->before_data,
            'after_data' => $this->after_data,
            'changed_fields' => $this->changed_fields,
            'reason' => $this->reason,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'uuid' => $this->user->uuid,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
            ] : null),
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
