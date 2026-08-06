<?php

use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Models\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $statuses = [
            ['slug' => ContentStatusSlug::PendingReview->value, 'name' => 'Pending Review', 'color' => '#f59e0b', 'sort_order' => 15],
            ['slug' => ContentStatusSlug::Reviewed->value, 'name' => 'Reviewed', 'color' => '#8b5cf6', 'sort_order' => 16],
            ['slug' => ContentStatusSlug::Approved->value, 'name' => 'Approved', 'color' => '#14b8a6', 'sort_order' => 17],
            ['slug' => ContentStatusSlug::Rejected->value, 'name' => 'Rejected', 'color' => '#ef4444', 'sort_order' => 18],
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

        Schema::table('contents', function (Blueprint $table) {
            $table->string('current_workflow_level', 32)->nullable()->after('version')->index();
            $table->text('last_workflow_comment')->nullable()->after('current_workflow_level');
            $table->foreignId('submitted_by')->nullable()->after('published_by')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('approved_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });

        Schema::create('content_workflow_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('action', 32)->index();
            $table->string('approval_level', 32)->nullable()->index();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['content_id', 'created_at']);
            $table->index(['to_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_workflow_histories');

        Schema::table('contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn('submitted_at');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('reviewed_at');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn('rejected_at');
            $table->dropColumn(['current_workflow_level', 'last_workflow_comment']);
        });

        ContentStatus::query()->whereIn('slug', [
            ContentStatusSlug::PendingReview->value,
            ContentStatusSlug::Reviewed->value,
            ContentStatusSlug::Approved->value,
            ContentStatusSlug::Rejected->value,
        ])->delete();
    }
};
