<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_crash_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('application_version_id')->nullable()->constrained('application_versions')->nullOnDelete();
            $table->string('version_label', 64)->nullable()->index();
            $table->string('type', 32)->default('crash')->index();
            $table->string('severity', 32)->default('error')->index();
            $table->string('status', 32)->default('open')->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->longText('crash_log')->nullable();
            $table->string('fingerprint', 128)->nullable()->index();
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->string('device_id', 128)->nullable()->index();
            $table->string('device_model', 128)->nullable()->index();
            $table->string('device_manufacturer', 128)->nullable();
            $table->string('device_os', 64)->nullable()->index();
            $table->string('device_os_version', 64)->nullable();
            $table->json('device_meta')->nullable();
            $table->string('endpoint', 512)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->decimal('memory_usage_mb', 10, 2)->nullable();
            $table->decimal('battery_level', 5, 2)->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['application_id', 'type', 'status'], 'app_crashes_app_type_status_idx');
            $table->index(['application_id', 'occurred_at'], 'app_crashes_app_occurred_idx');
            $table->index(['application_id', 'fingerprint'], 'app_crashes_app_fingerprint_idx');
        });

        Schema::create('application_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('application_version_id')->nullable()->constrained('application_versions')->nullOnDelete();
            $table->foreignId('environment_id')->nullable()->constrained('application_environments')->nullOnDelete();
            $table->string('version_label', 64)->nullable();
            $table->timestamp('recorded_at')->index();
            $table->unsignedTinyInteger('health_score')->default(100);
            $table->decimal('crash_rate', 8, 4)->default(0);
            $table->decimal('anr_rate', 8, 4)->default(0);
            $table->decimal('api_error_rate', 8, 4)->default(0);
            $table->unsignedInteger('avg_response_time_ms')->default(0);
            $table->decimal('avg_memory_usage_mb', 10, 2)->default(0);
            $table->decimal('avg_battery_usage', 8, 2)->default(0);
            $table->unsignedInteger('crash_count')->default(0);
            $table->unsignedInteger('anr_count')->default(0);
            $table->unsignedInteger('api_error_count')->default(0);
            $table->unsignedInteger('sample_size')->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['application_id', 'recorded_at'], 'app_health_metrics_app_recorded_idx');
        });

        Schema::create('application_monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('name');
            $table->string('metric', 64)->index();
            $table->string('operator', 16)->default('gte');
            $table->decimal('threshold', 12, 4);
            $table->string('severity', 32)->default('warning')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('cooldown_minutes')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['application_id', 'is_active'], 'app_mon_alerts_app_active_idx');
        });

        Schema::create('application_monitoring_alert_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('alert_id')->constrained('application_monitoring_alerts')->cascadeOnDelete();
            $table->string('metric', 64);
            $table->decimal('threshold', 12, 4);
            $table->decimal('observed_value', 12, 4);
            $table->string('severity', 32)->default('warning');
            $table->string('status', 32)->default('open')->index();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('triggered_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['application_id', 'triggered_at'], 'app_mon_alert_events_app_trig_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_monitoring_alert_events');
        Schema::dropIfExists('application_monitoring_alerts');
        Schema::dropIfExists('application_health_metrics');
        Schema::dropIfExists('application_crash_reports');
    }
};
