<?php

namespace Database\Seeders;

use App\Domains\Support\Models\KnowledgeCategory;
use App\Domains\Support\Models\KnowledgeTag;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Getting Started', 'slug' => 'getting-started', 'icon' => 'rocket', 'sort_order' => 1],
            ['name' => 'Account & Billing', 'slug' => 'account-billing', 'icon' => 'credit-card', 'sort_order' => 2],
            ['name' => 'Mobile Apps', 'slug' => 'mobile-apps', 'icon' => 'device-phone-mobile', 'sort_order' => 3],
            ['name' => 'Integrations', 'slug' => 'integrations', 'icon' => 'puzzle-piece', 'sort_order' => 4],
            ['name' => 'Troubleshooting', 'slug' => 'troubleshooting', 'icon' => 'wrench', 'sort_order' => 5],
            ['name' => 'Release Notes', 'slug' => 'release-notes', 'icon' => 'newspaper', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            KnowledgeCategory::query()->firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                    'description' => $category['name'].' knowledge articles',
                ]
            );
        }

        foreach (['setup', 'security', 'api', 'ios', 'android', 'web'] as $index => $tag) {
            KnowledgeTag::query()->firstOrCreate(
                ['slug' => $tag],
                [
                    'name' => ucfirst($tag),
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
