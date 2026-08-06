<?php

namespace App\Domains\Ai\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'role' => $this->role?->value ?? $this->role,
            'content' => $this->content,
            'token_input' => $this->token_input,
            'token_output' => $this->token_output,
            'model' => $this->model,
            'finish_reason' => $this->finish_reason,
            'tool_calls' => $this->tool_calls,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
