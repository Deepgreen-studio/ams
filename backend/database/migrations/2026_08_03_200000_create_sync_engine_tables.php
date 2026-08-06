<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('direction', 32)->index();
            $table->string('default_mode', 32)->default('incremental');
            $table->string('trigger_type', 32)->default('manual')->index();
            $table->string('schedule_cron')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->string('source_path')->nullable();
            $table->string('target_path')->nullable();
            $table->string('entity_type', 100)->default('generic');
            $table->string('conflict_strategy', 32)->default('skip');
            $table->unsignedInteger('batch_size')->default(100);
            $table->string('cursor_field', 100)->nullable();
            $table->string('cursor_value')->nullable();
            $table->json('field_mapping')->nullable();
            $table->json('filters')->nullable();
            $table->json('options')->nullable();
            $table->json('record_snapshot')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['integration_id', 'is_enabled']);
            $table->index(['company_id', 'trigger_type']);
        });

        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sync_config_id')->constrained('sync_configs')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('trigger', 32)->index();
            $table->string('mode', 32)->index();
            $table->string('direction', 32);
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('imported')->default(0);
            $table->unsignedInteger('exported')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sync_config_id', 'created_at']);
            $table->index(['company_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sync_run_id')->constrained('sync_runs')->cascadeOnDelete();
            $table->foreignId('sync_config_id')->constrained('sync_configs')->cascadeOnDelete();
            $table->string('level', 20)->default('info')->index();
            $table->string('action', 40)->nullable()->index();
            $table->string('record_key')->nullable()->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['sync_run_id', 'created_at']);
            $table->index(['sync_config_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('sync_runs');
        Schema::dropIfExists('sync_configs');
    }
};
