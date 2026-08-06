<?php

namespace App\Domains\Users\Contracts;

/**
 * Future extension contracts for login history and bulk operations.
 * Kept for architecture readiness without implementing Phase 1.4+ modules.
 */
interface UserLoginHistoryContract
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordLogin(int $userId, array $attributes): void;
}

interface UserBulkActionContract
{
    /**
     * @param  list<string>  $identifiers
     * @return array<string, mixed>
     */
    public function bulkUpdateStatus(array $identifiers, string $status, int $actorId): array;
}
