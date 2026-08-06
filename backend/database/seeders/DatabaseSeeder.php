<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            SystemSettingsSeeder::class,
            WebhookEventSeeder::class,
            ContentFoundationSeeder::class,
            SupportSlaSeeder::class,
            KnowledgeBaseSeeder::class,
            SupportCannedResponseSeeder::class,
            NotificationChannelSeeder::class,
            NotificationTemplateSeeder::class,
            AutomationRulesSeeder::class,
            WorkflowSeeder::class,
            ScheduledJobSeeder::class,
            AiSeeder::class,
            AnalyticsFoundationSeeder::class,
            PortalCustomerUserSeeder::class,
            ConsentTypeSeeder::class,
            EasyCarbsCompanySeeder::class,
            EasyCareCompanySeeder::class,
        ]);
    }
}
