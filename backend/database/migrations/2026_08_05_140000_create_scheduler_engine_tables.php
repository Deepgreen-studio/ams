<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('job_type', 32)->index();
            $table->string('handler_key', 100)->index();
            $table->string('schedule_cron', 64)->nullable();
            $table->string('timezone', 64)->nullable()->default('UTC');
            $table->timestamp('run_at')->nullable()->index();
            $table->unsignedInteger('delay_minutes')->nullable();
            $table->string('queue_name', 64)->nullable()->default('default');
            $table->boolean('is_enabled')->default(true)->index();
            $table->boolean('without_overlapping')->default(true);
            $table->unsignedSmallInteger('max_attempts')->default(3);
            $table->unsignedInteger('timeout_seconds')->nullable();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->string('last_status', 32)->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['job_type', 'is_enabled'], 'sj_type_enabled_idx');
            $table->index(['handler_key', 'is_enabled'], 'sj_handler_enabled_idx');
        });

        Schema::create('scheduled_job_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('scheduled_job_id');
            $table->string('status', 32)->index();
            $table->string('trigger', 32)->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('queue_name', 64)->nullable();
            $table->string('queue_job_id', 100)->nullable()->index();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('scheduled_job_id', 'sjr_job_fk')
                ->references('id')->on('scheduled_jobs')->cascadeOnDelete();
            $table->index(['scheduled_job_id', 'status'], 'sjr_job_status_idx');
            $table->index(['created_at']);
        });

        Schema::create('scheduled_job_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('scheduled_job_run_id');
            $table->string('level', 16)->default('info')->index();
            $table->string('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->foreign('scheduled_job_run_id', 'sjl_run_fk')
                ->references('id')->on('scheduled_job_runs')->cascadeOnDelete();
            $table->index(['scheduled_job_run_id', 'created_at'], 'sjl_run_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_job_logs');
        Schema::dropIfExists('scheduled_job_runs');
        Schema::dropIfExists('scheduled_jobs');
    }
};
