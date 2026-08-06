<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('integration_id')->nullable()->constrained('integrations')->nullOnDelete();
            $table->string('scope', 32)->default('hub')->index();
            $table->unsignedTinyInteger('health_score')->default(0);
            $table->unsignedTinyInteger('performance_score')->default(0);
            $table->decimal('uptime_percent', 5, 2)->default(0);
            $table->decimal('downtime_percent', 5, 2)->default(0);
            $table->decimal('error_rate', 5, 2)->default(0);
            $table->unsignedInteger('avg_response_ms')->default(0);
            $table->decimal('webhook_success_rate', 5, 2)->default(0);
            $table->unsignedTinyInteger('queue_health_score')->default(0);
            $table->string('availability_status', 32)->default('unknown');
            $table->string('authentication_status', 32)->default('unknown');
            $table->string('rate_limit_status', 32)->default('unknown');
            $table->string('server_status', 32)->default('unknown');
            $table->json('metrics')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();

            $table->index(['scope', 'created_at']);
            $table->index(['integration_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('metric', 64)->index();
            $table->string('operator', 10)->default('gte');
            $table->decimal('threshold', 10, 2);
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('cooldown_minutes')->default(15);
            $table->json('channels')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('monitoring_alert_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('monitoring_alert_id')->constrained('monitoring_alerts')->cascadeOnDelete();
            $table->string('severity', 20)->default('warning')->index();
            $table->string('status', 20)->default('open')->index();
            $table->decimal('metric_value', 10, 2)->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['monitoring_alert_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_alert_events');
        Schema::dropIfExists('monitoring_alerts');
        Schema::dropIfExists('monitoring_snapshots');
    }
};
