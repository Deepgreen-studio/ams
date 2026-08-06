<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->date('metric_date')->index();
            $table->unsignedInteger('active_users')->default(0);
            $table->unsignedInteger('daily_users')->default(0);
            $table->unsignedInteger('monthly_users')->default(0);
            $table->unsignedInteger('avg_session_seconds')->default(0);
            $table->decimal('retention_d1', 8, 4)->default(0);
            $table->decimal('retention_d7', 8, 4)->default(0);
            $table->decimal('retention_d30', 8, 4)->default(0);
            $table->unsignedInteger('installs')->default(0);
            $table->unsignedInteger('uninstalls')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['application_id', 'metric_date'], 'app_analytics_daily_unique');
            $table->index(['application_id', 'metric_date'], 'app_analytics_daily_app_date_idx');
        });

        Schema::create('application_analytics_countries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->date('metric_date')->index();
            $table->string('country_code', 8)->index();
            $table->string('country_name', 128)->nullable();
            $table->unsignedInteger('users')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('installs')->default(0);
            $table->timestamps();

            $table->unique(['application_id', 'metric_date', 'country_code'], 'app_analytics_country_unique');
            $table->index(['application_id', 'metric_date'], 'app_analytics_country_app_date_idx');
        });

        Schema::create('application_analytics_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->date('metric_date')->index();
            $table->string('device_model', 128)->nullable()->index();
            $table->string('os_name', 64)->nullable()->index();
            $table->string('os_version', 64)->nullable();
            $table->unsignedInteger('users')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->timestamps();

            $table->index(['application_id', 'metric_date'], 'app_analytics_device_app_date_idx');
            $table->index(['application_id', 'os_name', 'os_version'], 'app_analytics_device_os_idx');
        });

        Schema::create('application_analytics_heatmaps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->date('metric_date')->index();
            $table->unsignedTinyInteger('day_of_week');
            $table->unsignedTinyInteger('hour');
            $table->unsignedInteger('activity_count')->default(0);
            $table->timestamps();

            $table->unique(['application_id', 'metric_date', 'day_of_week', 'hour'], 'app_analytics_heatmap_unique');
            $table->index(['application_id', 'metric_date'], 'app_analytics_heatmap_app_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_analytics_heatmaps');
        Schema::dropIfExists('application_analytics_devices');
        Schema::dropIfExists('application_analytics_countries');
        Schema::dropIfExists('application_analytics_daily');
    }
};
