<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_reports', function (Blueprint $table): void {
            $table->foreignId('owner_id')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
            $table->string('visibility', 32)->default('personal')->after('status')->index();
            $table->boolean('is_saved')->default(true)->after('visibility')->index();
            $table->boolean('is_scheduled')->default(false)->after('is_saved')->index();
            $table->json('columns')->nullable()->after('query_config');
            $table->json('filters')->nullable()->after('columns');
            $table->json('sorting')->nullable()->after('filters');
            $table->json('grouping')->nullable()->after('sorting');
            $table->json('chart_config')->nullable()->after('grouping');
            $table->json('layout')->nullable()->after('chart_config');
            $table->unsignedBigInteger('scheduled_job_id')->nullable()->after('schedule_config');

            $table->foreign('scheduled_job_id', 'analytics_reports_scheduled_job_fk')
                ->references('id')->on('scheduled_jobs')->nullOnDelete();

            $table->index(['owner_id', 'visibility'], 'analytics_reports_owner_visibility_idx');
            $table->index(['report_type', 'status'], 'analytics_reports_type_status_idx');
        });

        Schema::create('analytics_report_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('analytics_report_id')
                ->constrained('analytics_reports')
                ->cascadeOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->string('format', 32)->default('csv')->index();
            $table->string('trigger', 32)->default('manual')->index();
            $table->json('filters_snapshot')->nullable();
            $table->json('result_meta')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['analytics_report_id', 'created_at'], 'analytics_report_runs_report_created_idx');
            $table->index(['status', 'created_at'], 'analytics_report_runs_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_report_runs');

        Schema::table('analytics_reports', function (Blueprint $table): void {
            $table->dropForeign(['owner_id']);
            $table->dropForeign('analytics_reports_scheduled_job_fk');
            $table->dropIndex('analytics_reports_owner_visibility_idx');
            $table->dropIndex('analytics_reports_type_status_idx');
            $table->dropColumn([
                'owner_id',
                'visibility',
                'is_saved',
                'is_scheduled',
                'columns',
                'filters',
                'sorting',
                'grouping',
                'chart_config',
                'layout',
                'scheduled_job_id',
            ]);
        });
    }
};
