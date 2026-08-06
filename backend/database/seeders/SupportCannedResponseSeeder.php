<?php

namespace Database\Seeders;

use App\Domains\Support\Enums\CannedResponseVisibility;
use App\Domains\Support\Models\SupportCannedResponse;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupportCannedResponseSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->orderBy('id')->first();
        if (! $actor) {
            return;
        }

        $items = [
            [
                'title' => 'Greeting — first reply',
                'shortcut' => 'hello',
                'visibility' => CannedResponseVisibility::Shared->value,
                'body' => '<p>Hello {{customer_name}},</p><p>Thank you for contacting support. We have received your request and will look into this shortly.</p><p>Best regards,<br>Support Team</p>',
            ],
            [
                'title' => 'Request more information',
                'shortcut' => 'more-info',
                'visibility' => CannedResponseVisibility::Shared->value,
                'body' => '<p>Could you please provide a few more details so we can investigate?</p><ul><li>Steps to reproduce</li><li>Expected vs actual behavior</li><li>Screenshots or error messages</li></ul><p>We will continue as soon as we have this information.</p>',
            ],
            [
                'title' => 'Issue resolved follow-up',
                'shortcut' => 'resolved',
                'visibility' => CannedResponseVisibility::Shared->value,
                'body' => '<p>We believe this issue has been resolved. Please reply if you are still experiencing problems and we will reopen the ticket.</p><p>Thank you for your patience.</p>',
            ],
            [
                'title' => 'My personal acknowledgment',
                'shortcut' => 'ack',
                'visibility' => CannedResponseVisibility::Personal->value,
                'body' => '<p>Thanks for the update — I am reviewing this now and will follow up shortly.</p>',
            ],
        ];

        foreach ($items as $index => $item) {
            SupportCannedResponse::query()->updateOrCreate(
                [
                    'user_id' => $actor->id,
                    'shortcut' => $item['shortcut'],
                    'visibility' => $item['visibility'],
                ],
                [
                    'title' => $item['title'],
                    'body' => $item['body'],
                    'body_format' => 'html',
                    'is_active' => true,
                    'sort_order' => $index,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]
            );
        }
    }
}
