<?php

namespace App\Domains\Authentication\Services;

use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorService
{
    public function __construct(private readonly TotpService $totpService) {}

    /**
     * @return array{secret: string, otpauth_url: string}
     */
    public function beginEnrollment(User $user): array
    {
        $secret = $this->totpService->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return [
            'secret' => $secret,
            'otpauth_url' => $this->totpService->provisioningUri(
                $secret,
                (string) $user->email,
                (string) config('app.name', 'AMS')
            ),
        ];
    }

    /**
     * @return array{recovery_codes: list<string>}
     */
    public function confirmEnrollment(User $user, string $code): array
    {
        $secret = (string) $user->two_factor_secret;

        if ($secret === '' || ! $this->totpService->verify($secret, $code)) {
            throw new ApiException('The authentication code is invalid.', 422);
        }

        $plainCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(
                static fn (string $recoveryCode): string => Hash::make($recoveryCode),
                $plainCodes
            ),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return ['recovery_codes' => $plainCodes];
    }

    public function disable(User $user, string $password): void
    {
        if ($user->hasRole('super-admin')) {
            throw new ApiException('Super Admin accounts cannot disable multi-factor authentication.', 422);
        }

        if (! Hash::check($password, (string) $user->password)) {
            throw new ApiException('The password is incorrect.', 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function verifyLoginCode(User $user, string $code): bool
    {
        $code = trim($code);

        if ($user->two_factor_secret && $this->totpService->verify((string) $user->two_factor_secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($index = 0; $index < 8; $index++) {
            $codes[] = Str::upper(Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $hashes = $user->two_factor_recovery_codes;

        if (! is_array($hashes) || $hashes === []) {
            return false;
        }

        $normalized = Str::upper(preg_replace('/\s+/', '', $code) ?? '');

        foreach ($hashes as $index => $hash) {
            if (! is_string($hash) || ! Hash::check($normalized, $hash)) {
                continue;
            }

            unset($hashes[$index]);
            $user->forceFill([
                'two_factor_recovery_codes' => array_values($hashes),
            ])->save();

            return true;
        }

        return false;
    }
}
