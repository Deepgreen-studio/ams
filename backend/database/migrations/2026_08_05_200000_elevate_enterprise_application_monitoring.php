<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('level', 20)->default('info')->index();
            $table->string('category', 32)->index();
            $table->string('source', 64)->nullable()->index();
            $table->string('title', 255)->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->nullableMorphs('related');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['category', 'occurred_at']);
            $table->index(['level', 'occurred_at']);
            $table->index(['company_id', 'occurred_at']);
        });

        Schema::create('health_checks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('monitoring_snapshot_id')->nullable()
                ->constrained('monitoring_snapshots')->nullOnDelete();
            $table->string('check_type', 32)->index();
            $table->string('name');
            $table->string('status', 20)->default('unknown')->index();
            $table->unsignedInteger('response_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();

            $table->index(['check_type', 'checked_at']);
            $table->index(['status', 'checked_at']);
            $table->index(['company_id', 'check_type']);
        });

        Schema::create('service_status', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('service_key', 120);
            $table->string('service_type', 32)->index();
            $table->string('name');
            $table->string('status', 20)->default('unknown')->index();
            $table->timestamp('last_check_at')->nullable()->index();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->decimal('uptime_percent', 5, 2)->nullable();
            $table->unsignedInteger('avg_response_ms')->nullable();
            $table->decimal('error_rate', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'service_key'], 'service_status_company_key_unique');
            $table->index(['service_type', 'status']);
            $table->index(['status', 'last_check_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_status');
        Schema::dropIfExists('health_checks');
        Schema::dropIfExists('monitoring_logs');
    }
};
