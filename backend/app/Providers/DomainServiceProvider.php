<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Domain modules prepared for future route registration.
     *
     * @var list<string>
     */
    protected array $domains = [
        'Authentication',
        'Dashboard',
        'Users',
        'Roles',
        'Companies',
        'Applications',
        'Customers',
        'Integrations',
        'Queue',
        'Monitoring',
        'Releases',
        'Content',
        'Support',
        'Notifications',
        'Automation',
        'Workflows',
        'Scheduler',
        'Ai',
        'Analytics',
        'Compliance',
        'Reports',
        'Settings',
        'Audit',
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerDomainRoutes();
        $this->commands([
            \App\Domains\Support\Console\EvaluateSupportSlaCommand::class,
            \App\Domains\Automation\Console\ProcessAutomationRulesCommand::class,
            \App\Domains\Workflows\Console\ProcessWorkflowTimeoutsCommand::class,
            \App\Domains\Scheduler\Console\ProcessScheduledJobsCommand::class,
            \App\Domains\Ai\Console\UseGeminiCommand::class,
        ]);
    }

    protected function registerDomainRoutes(): void
    {
        foreach ($this->domains as $domain) {
            $routeFile = app_path("Domains/{$domain}/Routes/api.php");

            if (! File::exists($routeFile)) {
                continue;
            }

            Route::middleware('api')
                ->prefix('api/v1')
                ->group($routeFile);
        }
    }
}
