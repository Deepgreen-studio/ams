<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_releases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('application_version_id')->constrained('application_versions')->restrictOnDelete();
            $table->foreignId('environment_id')->nullable()->constrained('application_environments')->nullOnDelete();
            $table->string('name');
            $table->string('version_label', 64);
            $table->string('release_type', 32)->default('minor')->index();
            $table->string('status', 32)->default('planned')->index();
            $table->string('approval_status', 32)->default('not_required')->index();
            $table->string('rollback_status', 32)->default('none')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('deployment_date')->nullable()->index();
            $table->timestamp('deployed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rolled_back_at')->nullable();
            $table->foreignId('rollback_of_release_id')->nullable()->constrained('application_releases')->nullOnDelete();
            $table->text('plan_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['application_id', 'status'], 'app_releases_app_status_idx');
            $table->index(['application_id', 'scheduled_at'], 'app_releases_app_scheduled_idx');
            $table->index(['application_id', 'deployment_date'], 'app_releases_app_deploy_idx');
            $table->index('created_by');
            $table->index('updated_by');
        });

        Schema::create('application_release_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('release_id')->constrained('application_releases')->cascadeOnDelete();
            $table->string('locale', 16)->default('en');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('audience', 32)->default('public')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['release_id', 'sort_order'], 'app_release_notes_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_release_notes');
        Schema::dropIfExists('application_releases');
    }
};
