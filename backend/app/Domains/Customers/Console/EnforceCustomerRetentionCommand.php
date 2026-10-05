<?php

namespace App\Domains\Customers\Console;

use App\Domains\Customers\Services\CustomerService;
use Illuminate\Console\Command;

class EnforceCustomerRetentionCommand extends Command
{
    protected $signature = 'customers:enforce-retention {--limit=200 : Maximum customers to anonymize}';

    protected $description = 'Anonymize customers whose retention date has passed';

    public function handle(CustomerService $customerService): int
    {
        $count = $customerService->enforceRetention(max(1, (int) $this->option('limit')));
        $this->info("Anonymized {$count} customer(s) past their retention date.");

        return self::SUCCESS;
    }
}
