<?php

namespace App\Domains\Users\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'name' => $this->full_name ?: $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'gender' => $this->gender?->value ?? $this->gender,
            'date_of_birth' => optional($this->date_of_birth)?->toDateString(),
            'timezone' => $this->timezone,
            'language' => $this->language,
            'company_id' => $this->whenLoaded('companies', function () {
                $company = $this->companies->firstWhere('pivot.is_primary', true) ?? $this->companies->first();

                return $company?->uuid;
            }),
            'department_id' => $this->whenLoaded('department', fn () => $this->department?->uuid),
            'status' => $this->status?->value ?? $this->status,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(static fn ($role) => [
                    'id' => $role->id,
                    'uuid' => $role->uuid,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'is_system' => (bool) $role->is_system,
                ])->values();
            }),
            'is_active' => (bool) $this->is_active,
            'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'last_login_ip' => $this->when(
                $request->user()?->can('users.view'),
                $this->last_login_ip
            ),
            'created_by' => $this->whenLoaded('creator', fn () => $this->actorPayload($this->creator)),
            'updated_by' => $this->whenLoaded('updater', fn () => $this->actorPayload($this->updater)),
            'deleted_by' => $this->actorPayload($this->deleter),
            'deleted_by_name' => $this->actorName($this->deleter),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    /**
     * @return array{id: int, uuid: string|null, full_name: string, email: string|null}|null
     */
    private function actorPayload(mixed $actor): ?array
    {
        if (! $actor) {
            return null;
        }

        return [
            'id' => $actor->id,
            'uuid' => $actor->uuid,
            'full_name' => $this->actorName($actor),
            'email' => $actor->email,
        ];
    }

    private function actorName(mixed $actor): ?string
    {
        if (! $actor) {
            return null;
        }

        $name = trim((string) ($actor->full_name ?: $actor->name ?: ''));

        return $name !== '' ? $name : null;
    }
}
