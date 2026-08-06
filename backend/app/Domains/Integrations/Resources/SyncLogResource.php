<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'sync_run_id' => $this->sync_run_id,
            'sync_config_id' => $this->sync_config_id,
            'level' => $this->level,
            'action' => $this->action,
            'record_key' => $this->record_key,
            'message' => $this->message,
            'context' => $this->context,
            'created_at' => $this->created_at,
        ];
    }
}
