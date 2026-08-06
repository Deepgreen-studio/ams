<?php

namespace App\Domains\Audit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $duration = null;
        if ($this->logged_in_at && $this->logout_at) {
            $duration = $this->logged_in_at->diffInSeconds($this->logout_at);
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'login_at' => $this->logged_in_at,
            'logout_at' => $this->logout_at,
            'duration_seconds' => $duration,
            'ip_address' => $this->ip_address,
            'browser' => $this->browser,
            'operating_system' => $this->operating_system ?? $this->platform,
            'device' => $this->device,
            'country' => $this->country,
            'city' => $this->city,
            'status' => $this->status,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'uuid' => $this->user->uuid,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
