<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Repositories\NotificationLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationDeliveryLogService
{
    public function __construct(
        private readonly NotificationLogRepository $logRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->logRepository->paginateFiltered($filters);
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return $this->logRepository->statistics();
    }
}
