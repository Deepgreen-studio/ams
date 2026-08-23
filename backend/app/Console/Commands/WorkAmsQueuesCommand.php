<?php

namespace App\Console\Commands;

use App\Shared\Services\Queue\QueueManager;
use Illuminate\Console\Command;

class WorkAmsQueuesCommand extends Command
{
    protected $signature = 'ams:queue-work
                            {--listen : Reload the worker on code changes}
                            {--once : Process a single job}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--tries=3 : Number of times to attempt a job}
                            {--timeout=90 : Seconds a job may run}
                            {--sleep=3 : Seconds to wait when no job is available}';

    protected $description = 'Start a queue worker that listens to all AMS named queues';

    public function handle(QueueManager $queueManager): int
    {
        $queues = $queueManager->workerQueueList();
        $this->info("Processing AMS queues: {$queues}");

        $parameters = [
            '--queue' => $queues,
            '--tries' => $this->option('tries'),
            '--timeout' => $this->option('timeout'),
            '--sleep' => $this->option('sleep'),
        ];

        if ($this->option('once')) {
            $parameters['--once'] = true;
        }

        if ($this->option('stop-when-empty')) {
            $parameters['--stop-when-empty'] = true;
        }

        return $this->call(
            $this->option('listen') ? 'queue:listen' : 'queue:work',
            $parameters,
        );
    }
}
