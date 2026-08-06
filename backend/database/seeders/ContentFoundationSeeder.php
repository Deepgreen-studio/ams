<?php

namespace Database\Seeders;

use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Enums\ContentTypeSlug;
use App\Domains\Content\Models\ContentStatus;
use App\Domains\Content\Models\ContentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['slug' => ContentStatusSlug::Draft->value, 'name' => 'Draft', 'color' => '#94a3b8', 'sort_order' => 10],
            ['slug' => ContentStatusSlug::PendingReview->value, 'name' => 'Pending Review', 'color' => '#f59e0b', 'sort_order' => 15],
            ['slug' => ContentStatusSlug::Reviewed->value, 'name' => 'Reviewed', 'color' => '#8b5cf6', 'sort_order' => 16],
            ['slug' => ContentStatusSlug::Approved->value, 'name' => 'Approved', 'color' => '#14b8a6', 'sort_order' => 17],
            ['slug' => ContentStatusSlug::Rejected->value, 'name' => 'Rejected', 'color' => '#ef4444', 'sort_order' => 18],
            ['slug' => ContentStatusSlug::Published->value, 'name' => 'Published', 'color' => '#10b981', 'sort_order' => 20],
            ['slug' => ContentStatusSlug::Scheduled->value, 'name' => 'Scheduled', 'color' => '#3b82f6', 'sort_order' => 30],
            ['slug' => ContentStatusSlug::Archived->value, 'name' => 'Archived', 'color' => '#64748b', 'sort_order' => 40],
        ];

        foreach ($statuses as $status) {
            ContentStatus::query()->firstOrCreate(
                ['slug' => $status['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'is_system' => true,
                    'sort_order' => $status['sort_order'],
                ]
            );
        }

        $types = [
            ContentTypeSlug::Page,
            ContentTypeSlug::Blog,
            ContentTypeSlug::News,
            ContentTypeSlug::Faq,
            ContentTypeSlug::Terms,
            ContentTypeSlug::Privacy,
            ContentTypeSlug::About,
            ContentTypeSlug::Help,
            ContentTypeSlug::Custom,
        ];

        foreach ($types as $index => $type) {
            ContentType::query()->firstOrCreate(
                ['slug' => $type->value],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $type->label(),
                    'description' => $type->label().' content type for headless CMS delivery.',
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }
}
