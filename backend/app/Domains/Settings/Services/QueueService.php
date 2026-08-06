<?php

namespace App\Domains\Settings\Services;

use App\Shared\Services\QueueManager;

class QueueService
{
    public function __construct(
        private readonly QueueManager $queueManager,
        private readonly SystemSettingService $settingService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $queue = (string) $this->settingService->getValue('queue', 'default_queue', 'default');

        return array_merge($this->queueManager->status(), [
            'configured_connection' => $this->settingService->getValue('queue', 'default_connection'),
            'default_queue' => $queue,
            'retry_attempts' => (int) $this->settingService->getValue('queue', 'retry_attempts', 3),
            'job_timeout' => (int) $this->settingService->getValue('queue', 'job_timeout', 90),
            'size' => $this->queueManager->size($queue),
        ]);
    }
}
