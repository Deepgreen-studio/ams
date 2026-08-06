<?php

namespace App\Shared\Services\Webhook;

use App\Shared\Exceptions\ApiException;

class SignatureValidator
{
    public function generate(string $payload, string $secret, string $algorithm = 'hmac_sha256'): string
    {
        $algo = $this->resolveAlgo($algorithm);
        $hash = hash_hmac($algo, $payload, $secret);

        return 'sha256='.$hash;
    }

    public function verify(string $payload, string $secret, ?string $providedSignature, string $algorithm = 'hmac_sha256'): bool
    {
        if ($algorithm === 'none') {
            return true;
        }

        if ($secret === '' || blank($providedSignature)) {
            return false;
        }

        $expected = $this->generate($payload, $secret, $algorithm);
        $normalizedProvided = $this->normalizeSignature((string) $providedSignature);

        return hash_equals($this->normalizeSignature($expected), $normalizedProvided)
            || hash_equals(hash_hmac($this->resolveAlgo($algorithm), $payload, $secret), $normalizedProvided);
    }

    public function assertValid(string $payload, string $secret, ?string $providedSignature, string $algorithm = 'hmac_sha256'): void
    {
        if (! $this->verify($payload, $secret, $providedSignature, $algorithm)) {
            throw new ApiException('Webhook signature verification failed.', 401);
        }
    }

    protected function resolveAlgo(string $algorithm): string
    {
        return match ($algorithm) {
            'hmac_sha1' => 'sha1',
            'hmac_sha256', 'sha256' => 'sha256',
            'none' => 'sha256',
            default => throw new ApiException('Unsupported webhook signature algorithm.', 422),
        };
    }

    protected function normalizeSignature(string $signature): string
    {
        $signature = trim($signature);
        if (str_contains($signature, '=')) {
            return strtolower((string) substr($signature, strpos($signature, '=') + 1));
        }

        return strtolower($signature);
    }
}
