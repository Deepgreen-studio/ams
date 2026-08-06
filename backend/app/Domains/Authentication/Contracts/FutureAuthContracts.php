<?php

namespace App\Domains\Authentication\Contracts;

/**
 * Extension points for future enterprise authentication providers.
 * Architecture only — no provider implementations in Phase 1.2.
 */
interface TwoFactorAuthenticatable
{
    public function hasTwoFactorEnabled(): bool;

    public function challengeTwoFactor(string $code): bool;
}

interface SocialAuthenticatable
{
    public function findOrCreateFromSocial(string $provider, array $profile): mixed;
}

interface MobileAuthenticatable
{
    public function issueMobileToken(mixed $user, string $deviceName): string;
}

interface SingleSignOnAuthenticatable
{
    public function authenticateViaSso(string $provider, array $payload): mixed;
}
