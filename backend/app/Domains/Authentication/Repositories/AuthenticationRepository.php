<?php

namespace App\Domains\Authentication\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticationRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findById(int|string $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByIdOrFail(int|string $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function credentialsAreValid(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->forceFill([
            'password' => $password,
        ])->save();

        return $user->refresh();
    }

    public function markEmailAsVerified(User $user): User
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $user->refresh();
    }

    public function updateLastLogin(User $user, ?string $ip): User
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        return $user->refresh();
    }

    public function createAccessToken(User $user, string $tokenName = 'api', array $abilities = ['*']): string
    {
        return $user->createToken($tokenName, $abilities)->plainTextToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    public function loadAuthRelations(User $user): User
    {
        return $user->loadMissing('roles', 'permissions', 'customer.company');
    }
}
