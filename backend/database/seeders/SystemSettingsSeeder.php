<?php

namespace Database\Seeders;

use App\Domains\Settings\Models\SettingGroup;
use App\Domains\Settings\Services\SystemSettingService;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'General', 'slug' => 'general', 'description' => 'Application general configuration', 'sort_order' => 1],
            ['name' => 'Email', 'slug' => 'email', 'description' => 'SMTP and mailer settings', 'sort_order' => 2],
            ['name' => 'Storage', 'slug' => 'storage', 'description' => 'Disk and upload settings', 'sort_order' => 3],
            ['name' => 'Security', 'slug' => 'security', 'description' => 'Security and session policies', 'sort_order' => 4],
            ['name' => 'API', 'slug' => 'api', 'description' => 'API behaviour settings', 'sort_order' => 5],
            ['name' => 'Queue', 'slug' => 'queue', 'description' => 'Background job settings', 'sort_order' => 6],
            ['name' => 'Cache', 'slug' => 'cache', 'description' => 'Cache driver configuration', 'sort_order' => 7],
            ['name' => 'Localization', 'slug' => 'localization', 'description' => 'Locale defaults', 'sort_order' => 8],
            ['name' => 'Notifications', 'slug' => 'notifications', 'description' => 'Notification channel defaults', 'sort_order' => 9],
        ];

        foreach ($groups as $group) {
            SettingGroup::query()->updateOrCreate(['slug' => $group['slug']], $group);
        }

        $actor = User::query()->orderBy('id')->first() ?? User::factory()->create([
            'email' => 'settings-seeder@example.com',
        ]);

        app(SystemSettingService::class)->seedDefaults($actor);
    }
}
