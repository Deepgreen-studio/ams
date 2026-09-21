<?php

namespace App\Domains\Users\Support;

use App\Models\User;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Http\Request;

final class UserLifecycleAuditor
{
    /**
     * Safe user attributes for audit snapshots (never includes secrets).
     *
     * @return array<string, mixed>
     */
    public static function snapshot(User $user): array
    {
        return [
            'uuid' => $user->uuid,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status?->value ?? $user->status,
            'gender' => $user->gender?->value ?? $user->gender,
            'timezone' => $user->timezone,
            'language' => $user->language,
            'date_of_birth' => optional($user->date_of_birth)?->toDateString(),
            'avatar' => $user->avatar,
        ];
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public static function trail(
        string $action,
        User $actor,
        User $subject,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?Request $request = null
    ): void {
        AuditHelper::trail(
            'users',
            $action,
            $actor,
            $subject,
            $before,
            $after,
            $reason,
            $request ?? request()
        );
    }
}
