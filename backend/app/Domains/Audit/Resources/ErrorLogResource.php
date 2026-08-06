<?php

namespace App\Domains\Audit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'exception' => $this->exception,
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
            'stack_trace' => $this->stack_trace,
            'url' => $this->url,
            'method' => $this->method,
            'ip_address' => $this->ip_address,
            'context' => $this->context,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'uuid' => $this->user->uuid,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
